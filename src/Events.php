<?php

declare(strict_types=1);

namespace Llhttp;

/**
 * Event name constants for HTTP parser callbacks
 */
class Events
{
    public const MESSAGE_BEGIN = 'messageBegin';
    public const URL = 'url';
    public const STATUS = 'status';
    public const HEADER_FIELD = 'headerField';
    public const HEADER_VALUE = 'headerValue';
    public const HEADERS_COMPLETE = 'headersComplete';
    public const BODY = 'body';
    public const MESSAGE_COMPLETE = 'messageComplete';

    /**
     * Get all available event names
     *
     * @return array<string>
     */
    public static function getAll(): array
    {
        return [
            self::MESSAGE_BEGIN,
            self::URL,
            self::STATUS,
            self::HEADER_FIELD,
            self::HEADER_VALUE,
            self::HEADERS_COMPLETE,
            self::BODY,
            self::MESSAGE_COMPLETE,
        ];
    }

    /**
     * Check if event name is valid
     */
    public static function isValid(string $event): bool
    {
        return in_array($event, self::getAll(), true);
    }
}