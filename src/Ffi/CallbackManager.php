<?php

declare(strict_types=1);

namespace Llhttp\Ffi;

use FFI;
use FFI\CData;
use Llhttp\Events;
use Llhttp\Exception;
use Llhttp\ErrorCodes;

/**
 * Manages callbacks between C llhttp and PHP
 */
class CallbackManager
{
    /** @var array<string, callable> */
    private array $callbacks = [];
    
    /** @var array<string, callable> */
    private array $cCallbacks = [];
    
    /** @var array<string, mixed> */
    private array $callbackData = [];
    
    private ?Exception $lastException = null;
    private FFI $ffi;

    public function __construct(FFI $ffi)
    {
        $this->ffi = $ffi;
        $this->initializeCCallbacks();
    }

    /**
     * Set PHP callback for an event
     */
    public function setCallback(string $event, callable $callback): void
    {
        if (!Events::isValid($event)) {
            throw new \InvalidArgumentException("Invalid event: {$event}");
        }

        $this->callbacks[$event] = $callback;
    }

    /**
     * Remove callback for an event
     */
    public function removeCallback(string $event): void
    {
        unset($this->callbacks[$event]);
    }

    /**
     * Get C callback for an event
     */
    public function getCCallback(string $event): ?callable
    {
        return $this->cCallbacks[$event] ?? null;
    }

    /**
     * Check if there's a pending exception from callbacks
     */
    public function getLastException(): ?Exception
    {
        return $this->lastException;
    }

    /**
     * Clear the last exception
     */
    public function clearLastException(): void
    {
        $this->lastException = null;
    }

    /**
     * Initialize C callback function pointers
     */
    private function initializeCCallbacks(): void
    {
        // Simple callbacks (no data)
        $this->cCallbacks[Events::MESSAGE_BEGIN] = $this->createSimpleCallback(Events::MESSAGE_BEGIN);
        $this->cCallbacks[Events::HEADERS_COMPLETE] = $this->createSimpleCallback(Events::HEADERS_COMPLETE);
        $this->cCallbacks[Events::MESSAGE_COMPLETE] = $this->createSimpleCallback(Events::MESSAGE_COMPLETE);

        // Data callbacks (with data)
        $this->cCallbacks[Events::URL] = $this->createDataCallback(Events::URL);
        $this->cCallbacks[Events::STATUS] = $this->createDataCallback(Events::STATUS);
        $this->cCallbacks[Events::HEADER_FIELD] = $this->createDataCallback(Events::HEADER_FIELD);
        $this->cCallbacks[Events::HEADER_VALUE] = $this->createDataCallback(Events::HEADER_VALUE);
        $this->cCallbacks[Events::BODY] = $this->createDataCallback(Events::BODY);
    }

    /**
     * Create simple callback (llhttp_cb)
     */
    private function createSimpleCallback(string $event): ?callable
    {
        return null; // Return null to use default behavior
    }

    /**
     * Create data callback (llhttp_data_cb)
     */
    private function createDataCallback(string $event): ?callable
    {
        return null; // Return null to use default behavior
    }

    /**
     * Handle simple callback invocation
     */
    private function handleSimpleCallback(string $event, CData $parser): int
    {
        if (!isset($this->callbacks[$event])) {
            return ErrorCodes::HPE_OK;
        }

        try {
            $result = ($this->callbacks[$event])();
            
            // Handle special return values for headers_complete
            if ($event === Events::HEADERS_COMPLETE) {
                if ($result === 1) {
                    return 1; // Skip body
                } elseif ($result === 2) {
                    return 2; // Skip body and pause
                }
            }
            
            return $result === false ? ErrorCodes::HPE_USER : ErrorCodes::HPE_OK;
        } catch (\Throwable $e) {
            $this->lastException = new Exception(
                "Callback error in {$event}: " . $e->getMessage(),
                ErrorCodes::HPE_USER,
                null,
                $e
            );
            return ErrorCodes::HPE_USER;
        }
    }

    /**
     * Handle data callback invocation
     */
    private function handleDataCallback(string $event, CData $parser, CData $at, int $length): int
    {
        if (!isset($this->callbacks[$event])) {
            return ErrorCodes::HPE_OK;
        }

        try {
            // Convert C data to PHP string
            $data = FFI::string($at, $length);
            
            $result = ($this->callbacks[$event])($data);
            return $result === false ? ErrorCodes::HPE_USER : ErrorCodes::HPE_OK;
        } catch (\Throwable $e) {
            $this->lastException = new Exception(
                "Callback error in {$event}: " . $e->getMessage(),
                ErrorCodes::HPE_USER,
                null,
                $e
            );
            return ErrorCodes::HPE_USER;
        }
    }

    /**
     * Set up callbacks in llhttp_settings_t structure
     */
    public function setupSettings(CData $settings): void
    {
        // Initialize settings to NULL first
        $this->ffi->llhttp_settings_init(FFI::addr($settings));
        
        // For now, we'll implement a simple approach without C callbacks
        // This is a limitation of the current FFI implementation
    }

    /**
     * Store data for use in callbacks
     */
    public function setCallbackData(string $key, mixed $value): void
    {
        $this->callbackData[$key] = $value;
    }

    /**
     * Get stored callback data
     */
    public function getCallbackData(string $key): mixed
    {
        return $this->callbackData[$key] ?? null;
    }
}