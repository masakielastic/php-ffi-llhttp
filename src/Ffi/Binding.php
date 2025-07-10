<?php

declare(strict_types=1);

namespace Llhttp\Ffi;

use FFI;
use FFI\CData;
use Llhttp\Exception;
use RuntimeException;

/**
 * Direct FFI binding to llhttp C library
 */
class Binding
{
    private static ?self $instance = null;
    private FFI $ffi;

    public function __construct(?string $libraryPath = null)
    {
        $this->initializeFfi($libraryPath);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(?string $libraryPath = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($libraryPath);
        }

        return self::$instance;
    }

    /**
     * Initialize FFI with llhttp library
     */
    private function initializeFfi(?string $libraryPath): void
    {
        $headerFile = __DIR__ . '/llhttp.h';
        
        if (!file_exists($headerFile)) {
            throw new RuntimeException("FFI header file not found: {$headerFile}");
        }

        $headerContent = file_get_contents($headerFile);
        if ($headerContent === false) {
            throw new RuntimeException("Cannot read FFI header file: {$headerFile}");
        }

        // Try to detect library location if not provided
        if ($libraryPath === null) {
            $libraryPath = $this->detectLibraryPath();
        }

        try {
            $this->ffi = FFI::cdef($headerContent, $libraryPath);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Failed to initialize FFI with llhttp library: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Detect llhttp library path
     */
    private function detectLibraryPath(): string
    {
        $candidates = [
            'libllhttp.so',
            'libllhttp.so.0',
            '/usr/local/lib/libllhttp.so',
            '/usr/lib/libllhttp.so',
            '/usr/lib/x86_64-linux-gnu/libllhttp.so',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Return default and let FFI handle the error
        return 'libllhttp.so';
    }

    /**
     * Create new parser instance
     */
    public function createParser(): CData
    {
        return $this->ffi->new('llhttp_t');
    }

    /**
     * Create new settings instance
     */
    public function createSettings(): CData
    {
        return $this->ffi->new('llhttp_settings_t');
    }

    /**
     * Initialize parser
     */
    public function initParser(CData $parser, int $type, CData $settings): void
    {
        $this->ffi->llhttp_init($parser, $type, FFI::addr($settings));
    }

    /**
     * Execute parser with data
     */
    public function execute(CData $parser, string $data): int
    {
        return $this->ffi->llhttp_execute($parser, $data, strlen($data));
    }

    /**
     * Finish parsing
     */
    public function finish(CData $parser): int
    {
        return $this->ffi->llhttp_finish($parser);
    }

    /**
     * Resume paused parser
     */
    public function resume(CData $parser): void
    {
        $this->ffi->llhttp_resume($parser);
    }

    /**
     * Get parser error code
     */
    public function getErrorCode(CData $parser): int
    {
        return $this->ffi->llhttp_get_errno($parser);
    }

    /**
     * Get HTTP major version
     */
    public function getHttpMajor(CData $parser): int
    {
        return $this->ffi->llhttp_get_http_major($parser);
    }

    /**
     * Get HTTP minor version
     */
    public function getHttpMinor(CData $parser): int
    {
        return $this->ffi->llhttp_get_http_minor($parser);
    }

    /**
     * Get HTTP method
     */
    public function getMethod(CData $parser): int
    {
        return $this->ffi->llhttp_get_method($parser);
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(CData $parser): int
    {
        return $this->ffi->llhttp_get_status_code($parser);
    }

    /**
     * Check if connection should keep alive
     */
    public function shouldKeepAlive(CData $parser): bool
    {
        return $this->ffi->llhttp_should_keep_alive($parser) !== 0;
    }

    /**
     * Check if message needs EOF
     */
    public function messageNeedsEof(CData $parser): bool
    {
        return $this->ffi->llhttp_message_needs_eof($parser) !== 0;
    }

    /**
     * Get error reason string
     */
    public function getErrorReason(CData $parser): ?string
    {
        $reason = $this->ffi->llhttp_get_error_reason($parser);
        return $reason !== null ? FFI::string($reason) : null;
    }

    /**
     * Get error name for error code
     */
    public function getErrorName(int $errno): string
    {
        $name = $this->ffi->llhttp_errno_name($errno);
        return FFI::string($name);
    }

    /**
     * Get method name for method code
     */
    public function getMethodName(int $method): string
    {
        $name = $this->ffi->llhttp_method_name($method);
        return FFI::string($name);
    }

    /**
     * Get FFI instance for advanced usage
     */
    public function getFfi(): FFI
    {
        return $this->ffi;
    }
}