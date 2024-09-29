<?php

namespace Phore\ServiceException\Types;

use Http\Client\Exception;
use Phore\ServiceException\ServiceException;

class InputValidationServiceException extends ServiceException
{

    public function __construct(
        string $message,
        array $details = null,
        Exception $innerError = null
    ) {
        parent::__construct(
            errorCode: "input_validation_error",
            message: $message,
            httpStatusCode: 400,
            details: $details,
            innerError: $innerError
        );
    }

}
