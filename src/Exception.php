<?php

declare(strict_types=1);

namespace Llhttp\Ffi;

use Exception as BaseException;

/**
 * Exception thrown when HTTP parsing errors occur
 */
class Exception extends BaseException
{
    private int $llhttpErrorCode;
    private ?string $errorPosition;

    public function __construct(
        string $message,
        int $llhttpErrorCode = 0,
        ?string $errorPosition = null,
        ?BaseException $previous = null
    ) {
        parent::__construct($message, $llhttpErrorCode, $previous);
        $this->llhttpErrorCode = $llhttpErrorCode;
        $this->errorPosition = $errorPosition;
    }

    /**
     * Get the llhttp error code
     */
    public function getLlhttpErrorCode(): int
    {
        return $this->llhttpErrorCode;
    }

    /**
     * Get the position in the input where the error occurred
     */
    public function getErrorPosition(): ?string
    {
        return $this->errorPosition;
    }
}
