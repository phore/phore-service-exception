<?php

namespace Phore\ServiceException\Types;

use Http\Client\Exception;
use Phore\ServiceException\ServiceException;
use Phore\ServiceException\T_ServiceException;

class InputValidationServiceException extends ServiceException
{

    public function __construct(
        string $message,
        array $details = null,
        \Throwable $parent = null
    ) {
        parent::__construct(new T_ServiceException(
            message: $message,
            errorCode: "input_validation",
            details: $details
        ), 400, $parent
        );
    }

}
