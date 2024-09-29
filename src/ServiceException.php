<?php

namespace Phore\ServiceException;

use Exception;
use JsonSerializable;

class ServiceException extends Exception implements JsonSerializable, \Stringable
{


    public function __construct(
        private T_ServiceException $payload,
        /**
         * If it is >400 it is the initial http status code to be returned
         */
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($this->payload->message, $code, $previous);
        if ($this->payload->exceptionType === null)
            $this->payload->exceptionType = (new \ReflectionObject($this))->getShortName();
        if ($this->payload->stackTrace === null)
            $this->payload->stackTrace = self::formatStackTrace($this);
        if ($code !== 0)
            $this->payload->httpStatusCode = $code;
    }

    private static function formatStackTrace ( \Throwable $e) : array {
        $trace = ["Thrown in " . $e->getFile() . " on line " . $e->getLine(),
            ...explode("\n", $e->getTraceAsString())
        ];
        return $trace;
    }



    /**
     * Create a ServiceException from an Exception or Error.
     */
    public static function fromThrowable(\Throwable $error, string $service, int $httpStatusCode = 500): ServiceException
    {
        // If the exception is already a ServiceException, return it directly
        if ($error instanceof ServiceException) {
            if ($error->payload->service === null)
                $error->payload->service = $service;
            return $error;
        }

        $innerError = null;
        if ($previous = $error->getPrevious()) {
            $innerError = self::fromThrowable($previous, $service);
        }
        $trace = self::formatStackTrace($error);


        $errorCode = "EXCEPTION";
        if ($error instanceof \InvalidArgumentException)
            $errorCode = "INVALID_ARGUMENT";
        if ($error instanceof \Error)
            $errorCode = "INTERNAL_ERROR";

        return new self(new T_ServiceException(
            message: $error->getMessage(),
            errorCode: $errorCode,
            service: $service,
            exceptionType: get_class($error),
            details: null,
            innerError: $innerError->payload ?? null,
            stackTrace: $trace,
            httpStatusCode: $httpStatusCode
        ), $httpStatusCode);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'error' => $this->payload->toArray()
        ];
    }

    // Getters for the properties
    public function getErrorCode(): string
    {
        return $this->payload->errorCode;
    }

    public function getService(): string
    {
        return $this->payload->service;
    }

    public function getTimestamp(): string
    {
        return $this->payload->timestamp;
    }

    public function getTraceId(): ?string
    {
        return $this->payload->traceId;
    }

    public function getExceptionType(): ?string
    {
        return $this->payload->exceptionType;
    }

    public function getDetails(): ?array
    {
        return $this->payload->details;
    }

    public function getInnerError(): ?ServiceException
    {
        return new ServiceException($this->payload->innerError);
    }

    public function getStackTrace(): array
    {
        return $this->payload->stackTrace;
    }

    public function getHttpStatusCode(): int
    {
        return $this->payload->httpStatusCode;
    }

    /**
     * Create a ServiceException from a JSON string.
     *
     * @param string $json The JSON string to parse.
     * @param bool $strict If true, throws an exception on invalid input; if false, returns null.
     * @return ServiceException|null
     * @throws \InvalidArgumentException if the JSON is invalid or does not represent a valid ServiceException, and $strict is true.
     */
    public static function fromJson(string $json, bool $strict = false): ?ServiceException
    {

        if (! str_starts_with($json, '{')) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid JSON provided: JSON must be an object.');
            } else {
                return null;
            }
        }
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
            } else {
                return null;
            }
        }


        return self::fromArray($data, $strict);
    }

    /**
     * Create a ServiceException from an associative array.
     *
     * @param array $data The data array to parse.
     * @param bool $strict If true, throws an exception on invalid input; if false, returns null.
     * @return ServiceException|null
     * @throws \InvalidArgumentException if the array does not contain required fields, and $strict is true.
     */
    public static function fromArray(array $data, bool $strict = false): ?ServiceException
    {

        if (!isset($data['error']) || !is_array($data['error'])) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid error format: "error" key missing or not an array.');
            } else {
                return null;
            }
        }
        $payload = T_ServiceException::fromArray($data["error"], $strict);

        if ($payload === null) {
            return null;
        }


        // Create and return the ServiceException
        return new self($payload);
    }

    /**
     * Provide a string representation of the ServiceException.
     *
     * @return string
     */
    public function __toString(): string
    {

        $output = "ServiceException [{$this->payload->errorCode}]: '{$this->getMessage()}' (Service: '{$this->payload->service}', HTTP Status Code: {$this->payload->httpStatusCode}, Thrown in {$this->payload->stackTrace[0]})";
        return $output;
    }


    public function __debugInfo(): array
    {
        return $this->toArray();
    }


    /**
     * Convert the exception to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->payload->toArray();
    }

    /**
     * Get the root cause of the exception chain.
     *
     * @return ServiceException
     */
    public function getRootCause(): T_ServiceException
    {
        return $this->payload->getRootCause();
    }

    /**
     * Get all exception messages, including inner exceptions.
     *
     * @return string[]
     */
    public function getAllMessages(): array
    {
        return $this->payload->getAllMessages();
    }


    /**
     * Convert the exception to a format suitable for API responses.
     *
     * @param int    $detailLevel Level of detail to include (0: minimal, 1: normal, 2: verbose).
     * @param string $environment The current environment ('production', 'staging', 'development').
     *
     * @return array
     */
    public function toApiResponse(int $detailLevel = 0, string $environment = 'production'): array
    {
        return $this->payload->toApiResponse($detailLevel, $environment);
    }

}
