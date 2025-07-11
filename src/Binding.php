<?php

declare(strict_types=1);

namespace Llhttp\Ffi;

use FFI;
use FFI\CData;
use Llhttp\Ffi\Exception;
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
     * Detect llhttp library path using environment variables and common locations
     */
    private function detectLibraryPath(): string
    {
        // 1. Check for explicit LLHTTP_LIBRARY_PATH environment variable
        $envPath = getenv('LLHTTP_LIBRARY_PATH');
        if ($envPath !== false && file_exists($envPath)) {
            return $envPath;
        }

        // 2. Check LD_LIBRARY_PATH directories
        $ldLibraryPath = getenv('LD_LIBRARY_PATH');
        if ($ldLibraryPath !== false) {
            $libraryDirs = explode(':', $ldLibraryPath);
            foreach ($libraryDirs as $dir) {
                $dir = trim($dir);
                if (empty($dir)) {
                    continue;
                }

                $candidates = [
                    $dir . '/libllhttp.so',
                    $dir . '/libllhttp.so.0',
                ];

                foreach ($candidates as $path) {
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
        }

        // 3. Check PKG_CONFIG_PATH for pkgconfig-based detection
        $pkgConfigPath = getenv('PKG_CONFIG_PATH');
        if ($pkgConfigPath !== false) {
            $pkgDirs = explode(':', $pkgConfigPath);
            foreach ($pkgDirs as $pkgDir) {
                $pkgDir = trim($pkgDir);
                if (empty($pkgDir)) {
                    continue;
                }

                // Try to find libdir from potential lib directory structure
                $libDir = dirname($pkgDir) . '/lib';
                if (is_dir($libDir)) {
                    $candidates = [
                        $libDir . '/libllhttp.so',
                        $libDir . '/libllhttp.so.0',
                    ];

                    foreach ($candidates as $path) {
                        if (file_exists($path)) {
                            return $path;
                        }
                    }
                }
            }
        }

        // 4. Check current working directory and relative paths
        $workingDirCandidates = [
            './libllhttp.so',
            './build/libllhttp.so',
            './llhttp/build/libllhttp.so',
            '../llhttp/build/libllhttp.so',
            './lib/libllhttp.so',
        ];

        foreach ($workingDirCandidates as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        // 5. Standard system library locations
        $systemCandidates = [
            'libllhttp.so', // Let the system linker find it
            'libllhttp.so.0',
            '/usr/local/lib/libllhttp.so',
            '/usr/lib/libllhttp.so',
            '/usr/lib/x86_64-linux-gnu/libllhttp.so',
            '/usr/lib64/libllhttp.so',
            '/lib/x86_64-linux-gnu/libllhttp.so',
            '/lib64/libllhttp.so',
        ];

        foreach ($systemCandidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Return default and let FFI/system linker handle it
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
        $this->ffi->llhttp_init(FFI::addr($parser), $type, FFI::addr($settings));
    }

    /**
     * Execute parser with data
     */
    public function execute(CData $parser, string $data): int
    {
        return $this->ffi->llhttp_execute(FFI::addr($parser), $data, strlen($data));
    }

    /**
     * Finish parsing
     */
    public function finish(CData $parser): int
    {
        return $this->ffi->llhttp_finish(FFI::addr($parser));
    }

    /**
     * Resume paused parser
     */
    public function resume(CData $parser): void
    {
        $this->ffi->llhttp_resume(FFI::addr($parser));
    }

    /**
     * Get parser error code
     */
    public function getErrorCode(CData $parser): int
    {
        return $this->ffi->llhttp_get_errno(FFI::addr($parser));
    }

    /**
     * Get HTTP major version
     */
    public function getHttpMajor(CData $parser): int
    {
        return $this->ffi->llhttp_get_http_major(FFI::addr($parser));
    }

    /**
     * Get HTTP minor version
     */
    public function getHttpMinor(CData $parser): int
    {
        return $this->ffi->llhttp_get_http_minor(FFI::addr($parser));
    }

    /**
     * Get HTTP method
     */
    public function getMethod(CData $parser): int
    {
        return $this->ffi->llhttp_get_method(FFI::addr($parser));
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(CData $parser): int
    {
        return $this->ffi->llhttp_get_status_code(FFI::addr($parser));
    }

    /**
     * Check if connection should keep alive
     */
    public function shouldKeepAlive(CData $parser): bool
    {
        return $this->ffi->llhttp_should_keep_alive(FFI::addr($parser)) !== 0;
    }

    /**
     * Check if message needs EOF
     */
    public function messageNeedsEof(CData $parser): bool
    {
        return $this->ffi->llhttp_message_needs_eof(FFI::addr($parser)) !== 0;
    }

    /**
     * Get error reason string
     */
    public function getErrorReason(CData $parser): ?string
    {
        $reason = $this->ffi->llhttp_get_error_reason(FFI::addr($parser));
        if ($reason === null) {
            return null;
        }
        // Check if it's already a string or needs FFI::string conversion
        if (is_string($reason)) {
            return $reason;
        }
        return FFI::string($reason);
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
        try {
            $name = $this->ffi->llhttp_method_name($method);
            // llhttp_method_name は直接文字列を返すことが判明
            if (is_string($name)) {
                return $name;
            }
            // CData の場合は FFI::string() を使用
            if ($name !== null && is_object($name)) {
                return FFI::string($name);
            }
            return 'UNKNOWN';
        } catch (\Throwable $e) {
            // フォールバック: 手動マッピング
            $methodNames = [
                0 => 'DELETE', 1 => 'GET', 2 => 'HEAD', 3 => 'POST', 4 => 'PUT',
                5 => 'CONNECT', 6 => 'OPTIONS', 7 => 'TRACE', 8 => 'COPY', 9 => 'LOCK',
                10 => 'MKCOL', 11 => 'MOVE', 12 => 'PROPFIND', 13 => 'PROPPATCH', 14 => 'SEARCH',
                15 => 'UNLOCK', 16 => 'BIND', 17 => 'REBIND', 18 => 'UNBIND', 19 => 'ACL',
                20 => 'REPORT', 21 => 'MKACTIVITY', 22 => 'CHECKOUT', 23 => 'MERGE', 24 => 'MSEARCH',
                25 => 'NOTIFY', 26 => 'SUBSCRIBE', 27 => 'UNSUBSCRIBE', 28 => 'PATCH', 29 => 'PURGE',
                30 => 'MKCALENDAR', 31 => 'LINK', 32 => 'UNLINK', 33 => 'SOURCE', 34 => 'PRI',
                35 => 'DESCRIBE', 36 => 'ANNOUNCE', 37 => 'SETUP', 38 => 'PLAY', 39 => 'PAUSE',
                40 => 'TEARDOWN', 41 => 'GET_PARAMETER', 42 => 'SET_PARAMETER', 43 => 'REDIRECT',
                44 => 'RECORD', 45 => 'FLUSH', 46 => 'QUERY'
            ];
            return $methodNames[$method] ?? 'UNKNOWN';
        }
    }

    /**
     * Get FFI instance for advanced usage
     */
    public function getFfi(): FFI
    {
        return $this->ffi;
    }
}
