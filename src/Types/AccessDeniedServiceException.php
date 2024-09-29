<?php

namespace Phore\ServiceException\Types;

use Phore\ServiceException\ServiceException;
use Phore\ServiceException\T_ServiceException;

class AccessDeniedServiceException extends ServiceException
{

        public function __construct(
            string $message,
            array $details = null,
            \Throwable $parent = null
        ) {
            parent::__construct(new T_ServiceException(
                message: $message,
                errorCode: "access_denied",
                details: $details
            ), 403, $parent);
        }

}
