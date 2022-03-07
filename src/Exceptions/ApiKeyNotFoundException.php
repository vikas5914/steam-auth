<?php

namespace Vikas5914\Exceptions;

use Exception;

class ApiKeyNotFoundException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @param integer $code
     * @param Exception|null $previous
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
