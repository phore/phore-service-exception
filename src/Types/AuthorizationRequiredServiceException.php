<?php

namespace Phore\ServiceException\Types;

use Phore\ServiceException\ServiceException;
use Phore\ServiceException\T_ServiceException;

class AuthorizationRequiredServiceException extends ServiceException
{

    public function __construct($message = "Authorization is required for this endpoint", \Throwable $previous = null)
    {
        parent::__construct(new T_ServiceException(
            message: $message,
            errorCode: "authorization_required",
            httpStatusCode: 401
        ), 401, $previous);
    }
}
