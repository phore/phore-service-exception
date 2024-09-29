<?php

namespace Phore\ServiceException\Types;

use Phore\ServiceException\ServiceException;

class AccessDeniedServiceException extends ServiceException
{

        public function __construct(
            string $message,
            array $details = null,
            \Exception $innerError = null
        ) {
            parent::__construct(
                errorCode: "access_denied",
                message: $message,
                httpStatusCode: 403,
                details: $details,
                innerError: $innerError
            );
        }

}
