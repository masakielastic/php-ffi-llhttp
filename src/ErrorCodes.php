<?php

declare(strict_types=1);

namespace Llhttp\Ffi;

/**
 * llhttp error code constants
 */
class ErrorCodes
{
    public const HPE_OK = 0;
    public const HPE_INTERNAL = 1;
    public const HPE_STRICT = 2;
    public const HPE_CR_EXPECTED = 25;
    public const HPE_LF_EXPECTED = 3;
    public const HPE_UNEXPECTED_CONTENT_LENGTH = 4;
    public const HPE_CLOSED_CONNECTION = 5;
    public const HPE_INVALID_METHOD = 6;
    public const HPE_INVALID_URL = 7;
    public const HPE_INVALID_CONSTANT = 8;
    public const HPE_INVALID_VERSION = 9;
    public const HPE_INVALID_HEADER_TOKEN = 10;
    public const HPE_INVALID_CONTENT_LENGTH = 11;
    public const HPE_INVALID_CHUNK_SIZE = 12;
    public const HPE_SIBLING_MESSAGE_IN_PROGRESS = 13;
    public const HPE_UPGRADING = 14;
    public const HPE_PAUSED = 15;
    public const HPE_PAUSED_UPGRADE = 16;
    public const HPE_PAUSED_H2_UPGRADE = 17;
    public const HPE_USER = 18;

    /**
     * Get error message for error code
     */
    public static function getMessage(int $code): string
    {
        return match ($code) {
            self::HPE_OK => 'Success',
            self::HPE_INTERNAL => 'Internal parser error',
            self::HPE_STRICT => 'Strict mode assertion failed',
            self::HPE_CR_EXPECTED => 'Expected CR following LF',
            self::HPE_LF_EXPECTED => 'Expected LF after CR',
            self::HPE_UNEXPECTED_CONTENT_LENGTH => 'Unexpected content-length header',
            self::HPE_CLOSED_CONNECTION => 'Connection closed before message completed',
            self::HPE_INVALID_METHOD => 'Invalid HTTP method',
            self::HPE_INVALID_URL => 'Invalid URL',
            self::HPE_INVALID_CONSTANT => 'Invalid constant string',
            self::HPE_INVALID_VERSION => 'Invalid HTTP version',
            self::HPE_INVALID_HEADER_TOKEN => 'Invalid header token',
            self::HPE_INVALID_CONTENT_LENGTH => 'Invalid content-length value',
            self::HPE_INVALID_CHUNK_SIZE => 'Invalid chunk size',
            self::HPE_SIBLING_MESSAGE_IN_PROGRESS => 'Sibling message in progress',
            self::HPE_UPGRADING => 'Connection is upgrading',
            self::HPE_PAUSED => 'Parser is paused',
            self::HPE_PAUSED_UPGRADE => 'Parser is paused on upgrade',
            self::HPE_PAUSED_H2_UPGRADE => 'Parser is paused on H2 upgrade',
            self::HPE_USER => 'User callback error',
            default => 'Unknown error',
        };
    }
}
