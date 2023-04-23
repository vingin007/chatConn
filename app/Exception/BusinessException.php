<?php

declare(strict_types=1);

namespace App\Exception;

use Hyperf\Server\Exception\ServerException;
use PHPUnit\Framework\Exception;
use Throwable;

class BusinessException extends Exception
{
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const INTERNAL_SERVER_ERROR = 500;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;

    public function __construct($errorCode, $errorMessage = "", $code = 0, Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        parent::__construct($errorMessage, $code, $previous);
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}
