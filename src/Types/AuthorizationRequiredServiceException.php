<?php

namespace Phore\ServiceException\Types;

use Phore\ServiceException\ServiceException;

class AuthorizationRequiredServiceException extends ServiceException
{

    public function __construct($message = "Authorization is required for this endpoint")
    {
        parent::__construct("authorization_required", $message, httpStatusCode: 401);
    }
}
