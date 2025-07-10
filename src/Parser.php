<?php

declare(strict_types=1);

namespace Llhttp;

use FFI\CData;
use Llhttp\Ffi\Binding;
use Llhttp\Ffi\CallbackManager;

/**
 * Main HTTP parser class with event-driven API
 */
class Parser
{
    public const TYPE_REQUEST = 0;
    public const TYPE_RESPONSE = 1;

    private Binding $binding;
    private CallbackManager $callbackManager;
    private CData $parser;
    private CData $settings;
    private bool $paused = false;
    private int $type;
    private bool $initialized = false;

    /** @var array<string, mixed> */
    private array $collectedHeaders = [];
    private ?string $currentHeaderField = null;
    private ?string $currentHeaderValue = null;

    public function __construct(int $type, ?string $libraryPath = null)
    {
        if (!in_array($type, [self::TYPE_REQUEST, self::TYPE_RESPONSE], true)) {
            throw new \InvalidArgumentException('Invalid parser type. Use Parser::TYPE_REQUEST or Parser::TYPE_RESPONSE');
        }

        $this->type = $type;
        $this->binding = Binding::getInstance($libraryPath);
        $this->callbackManager = new CallbackManager($this->binding->getFfi());
        
        $this->initializeParser();
        $this->setupInternalCallbacks();
    }

    /**
     * Register event callback
     */
    public function on(string $event, callable $callback): self
    {
        $this->callbackManager->setCallback($event, $callback);
        return $this;
    }

    /**
     * Remove event callback
     */
    public function off(string $event): self
    {
        $this->callbackManager->removeCallback($event);
        return $this;
    }

    /**
     * Execute parsing on data
     */
    public function execute(string $data): void
    {
        if (!$this->initialized) {
            throw new Exception('Parser not properly initialized');
        }

        $this->callbackManager->clearLastException();
        
        $result = $this->binding->execute($this->parser, $data);
        
        // Check for callback exceptions first
        $callbackException = $this->callbackManager->getLastException();
        if ($callbackException !== null) {
            throw $callbackException;
        }

        // Check for parser errors
        if ($result !== ErrorCodes::HPE_OK) {
            $this->handleParseError($result);
        }
    }

    /**
     * Finish parsing (call at end of stream)
     */
    public function finish(): void
    {
        if (!$this->initialized) {
            return;
        }

        $result = $this->binding->finish($this->parser);
        
        if ($result !== ErrorCodes::HPE_OK) {
            $this->handleParseError($result);
        }
    }

    /**
     * Pause the parser
     */
    public function pause(): void
    {
        $this->paused = true;
    }

    /**
     * Resume the parser
     */
    public function resume(): void
    {
        if ($this->paused) {
            $this->binding->resume($this->parser);
            $this->paused = false;
        }
    }

    /**
     * Reset parser to initial state
     */
    public function reset(): void
    {
        $this->initializeParser();
        $this->paused = false;
        $this->collectedHeaders = [];
        $this->currentHeaderField = null;
        $this->currentHeaderValue = null;
    }

    /**
     * Check if parser is paused
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * Get HTTP major version
     */
    public function getHttpMajor(): int
    {
        return $this->binding->getHttpMajor($this->parser);
    }

    /**
     * Get HTTP minor version
     */
    public function getHttpMinor(): int
    {
        return $this->binding->getHttpMinor($this->parser);
    }

    /**
     * Get HTTP method (for requests)
     */
    public function getMethod(): int
    {
        return $this->binding->getMethod($this->parser);
    }

    /**
     * Get HTTP method name (for requests)
     */
    public function getMethodName(): string
    {
        return $this->binding->getMethodName($this->getMethod());
    }

    /**
     * Get HTTP status code (for responses)
     */
    public function getStatusCode(): int
    {
        return $this->binding->getStatusCode($this->parser);
    }

    /**
     * Check if connection should keep alive
     */
    public function shouldKeepAlive(): bool
    {
        return $this->binding->shouldKeepAlive($this->parser);
    }

    /**
     * Check if message needs EOF to complete
     */
    public function messageNeedsEof(): bool
    {
        return $this->binding->messageNeedsEof($this->parser);
    }

    /**
     * Get parser type
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Get collected headers
     *
     * @return array<string, string|array<string>>
     */
    public function getHeaders(): array
    {
        return $this->collectedHeaders;
    }

    /**
     * Initialize parser and settings
     */
    private function initializeParser(): void
    {
        $this->parser = $this->binding->createParser();
        $this->settings = $this->binding->createSettings();
        
        $this->callbackManager->setupSettings($this->settings);
        $this->binding->initParser($this->parser, $this->type, $this->settings);
        
        $this->initialized = true;
    }

    /**
     * Set up internal header collection callbacks
     */
    private function setupInternalCallbacks(): void
    {
        // Header field callback
        $this->callbackManager->setCallback(Events::HEADER_FIELD, function (string $data): void {
            // If we have a pending header value, save the previous header
            if ($this->currentHeaderField !== null && $this->currentHeaderValue !== null) {
                $this->addHeader($this->currentHeaderField, $this->currentHeaderValue);
                $this->currentHeaderValue = null;
            }
            
            // Start new header field or continue existing one
            if ($this->currentHeaderField === null) {
                $this->currentHeaderField = $data;
            } else {
                $this->currentHeaderField .= $data;
            }
        });

        // Header value callback
        $this->callbackManager->setCallback(Events::HEADER_VALUE, function (string $data): void {
            if ($this->currentHeaderValue === null) {
                $this->currentHeaderValue = $data;
            } else {
                $this->currentHeaderValue .= $data;
            }
        });

        // Headers complete callback
        $this->callbackManager->setCallback(Events::HEADERS_COMPLETE, function (): void {
            // Save the last header if pending
            if ($this->currentHeaderField !== null && $this->currentHeaderValue !== null) {
                $this->addHeader($this->currentHeaderField, $this->currentHeaderValue);
            }
            
            $this->currentHeaderField = null;
            $this->currentHeaderValue = null;
        });
    }

    /**
     * Add header to collection, handling multiple values
     */
    private function addHeader(string $name, string $value): void
    {
        $name = strtolower($name);
        
        if (isset($this->collectedHeaders[$name])) {
            // Convert to array if not already
            if (!is_array($this->collectedHeaders[$name])) {
                $this->collectedHeaders[$name] = [$this->collectedHeaders[$name]];
            }
            $this->collectedHeaders[$name][] = $value;
        } else {
            $this->collectedHeaders[$name] = $value;
        }
    }

    /**
     * Handle parse errors
     */
    private function handleParseError(int $errorCode): void
    {
        $errorMessage = ErrorCodes::getMessage($errorCode);
        $errorReason = $this->binding->getErrorReason($this->parser);
        
        $message = $errorMessage;
        if ($errorReason !== null) {
            $message .= ': ' . $errorReason;
        }

        throw new Exception($message, $errorCode);
    }
}