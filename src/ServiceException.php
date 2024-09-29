<?php

namespace Phore\ServiceException;

use Exception;
use JsonSerializable;

class ServiceException extends Exception implements JsonSerializable, \Stringable
{
    private string $errorCode;
    private ?string $service;
    private string $timestamp;
    private ?string $traceId;
    private ?string $exceptionType;
    private ?array $details;
    private ?ServiceException $innerError;
    private array $stackTrace;
    private int $httpStatusCode;

    public function __construct(
        string $errorCode,
        string $message,
        ?string $service = null,
        int $httpStatusCode = 500,
        ?string $timestamp = null,
        ?string $traceId = null,
        ?string $exceptionType = null,
        /**
         * Additional details about the error.
         */
        ?array $details = null,
        ?ServiceException $innerError = null,
        ?array $stackTrace = null
    ) {
        parent::__construct($message);

        $this->errorCode = $errorCode;
        $this->service = $service;
        $this->httpStatusCode = $httpStatusCode;
        $this->timestamp = $timestamp ?? (new \DateTime())->format('c');
        $this->traceId = $traceId;
        $this->exceptionType = $exceptionType ?? (new \ReflectionClass($this))->getShortName();
        $this->details = $details;
        $this->innerError = $innerError;
        $this->stackTrace = $stackTrace ?? self::formatStackTrace($this);
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
            if ($error->service === null)
                $error->service = $service;
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

        return new self(
            errorCode: $errorCode,
            message: $error->getMessage(),
            service: $service,
            httpStatusCode: $httpStatusCode,
            exceptionType: get_class($error),
            details: null,
            innerError: $innerError,
            stackTrace: $trace
        );
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'error' => array_filter([
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'service' => $this->service,
                'http_status_code' => $this->httpStatusCode,
                'timestamp' => $this->timestamp,
                'trace_id' => $this->traceId,
                'exception_type' => $this->exceptionType,
                'details' => $this->details,
                'stack_trace' => $this->stackTrace,
                'inner_error' => $this->innerError ? $this->innerError->jsonSerialize()['error'] : null,
            ], function ($value) {
                return $value !== null;
            })
        ];
    }

    // Getters for the properties
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function getExceptionType(): ?string
    {
        return $this->exceptionType;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function getInnerError(): ?ServiceException
    {
        return $this->innerError;
    }

    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
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

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
            } else {
                return null;
            }
        }

        if (!isset($data['error']) || !is_array($data['error'])) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid error format: "error" key missing or not an array.');
            } else {
                return null;
            }
        }

        return self::fromArray($data['error'], $strict);
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
        // Validate required fields
        $requiredFields = ['code', 'message', 'service', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                if ($strict) {
                    throw new \InvalidArgumentException("Invalid error format: \"$field\" is required.");
                } else {
                    return null;
                }
            }
        }

        // Validate data types
        if (
            !is_string($data['code']) ||
            !is_string($data['message']) ||
            !is_string($data['service']) ||
            !is_string($data['timestamp'])
        ) {
            if ($strict) {
                throw new \InvalidArgumentException('Invalid data types for required fields.');
            } else {
                return null;
            }
        }

        // Parse the inner error if present
        $innerError = null;
        if (isset($data['inner_error'])) {
            if (is_array($data['inner_error'])) {
                $innerError = self::fromArray($data['inner_error'], $strict);
            } else {
                if ($strict) {
                    throw new \InvalidArgumentException('Invalid inner_error format: expected an array.');
                } else {
                    return null;
                }
            }
        }

        // Parse the stack trace if present
        $stackTrace = $data['stack_trace'] ?? [];

        // Create and return the ServiceException
        return new self(
            errorCode: $data['code'],
            message: $data['message'],
            service: $data['service'],
            httpStatusCode: isset($data['http_status_code']) ? (int) $data['http_status_code'] : 500,
            timestamp: $data['timestamp'],
            traceId: $data['trace_id'] ?? null,
            exceptionType: $data['exception_type'] ?? null,
            details: $data['details'] ?? null,
            innerError: $innerError,
            stackTrace: is_array($stackTrace) ? $stackTrace : []
        );
    }

    /**
     * Provide a string representation of the ServiceException.
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = "ServiceException [{$this->errorCode}]: {$this->getMessage()}";
        $output .= "\nService: {$this->service}";
        $output .= "\nHTTP Status Code: {$this->httpStatusCode}";
        $output .= "\nTimestamp: {$this->timestamp}";
        if ($this->traceId) {
            $output .= "\nTrace ID: {$this->traceId}";
        }
        $output .= "\nException Type: {$this->exceptionType}";
        if ($this->details) {
            $output .= "\nDetails: " . json_encode($this->details, JSON_PRETTY_PRINT);
        }
        $output .= "\nStack Trace:\n" . implode("\n  ", $this->stackTrace);

        if ($this->innerError) {
            $output .= "\nCaused by:\n" . $this->innerError->__toString();
        }

        return $output;
    }



    /**
     * Convert the exception to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'service' => $this->service,
            'http_status_code' => $this->httpStatusCode,
            'timestamp' => $this->timestamp,
            'trace_id' => $this->traceId,
            'exception_type' => $this->exceptionType,
            'details' => $this->details,
            'stack_trace' => $this->stackTrace,
            'inner_error' => $this->innerError ? $this->innerError->toArray() : null,
        ];
    }

    /**
     * Get the root cause of the exception chain.
     *
     * @return ServiceException
     */
    public function getRootCause(): ServiceException
    {
        $current = $this;
        while ($current->innerError !== null) {
            $current = $current->innerError;
        }
        return $current;
    }

    /**
     * Get all exception messages, including inner exceptions.
     *
     * @return string[]
     */
    public function getAllMessages(): array
    {
        $messages = [$this->getMessage()];
        if ($this->innerError) {
            $messages = array_merge($messages, $this->innerError->getAllMessages());
        }
        return $messages;
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
        // Determine whether to include sensitive information
        $includeSensitiveInfo = ($environment !== 'production') && ($detailLevel > 0);

        // Base error structure
        $error = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'service' => $this->service,
            'http_status_code' => $this->httpStatusCode,
        ];

        // Include additional details based on detail level
        if ($detailLevel >= 1) {
            $error['timestamp'] = $this->timestamp;
            $error['trace_id'] = $this->traceId;
            $error['exception_type'] = $this->exceptionType;
        }

        // Include sensitive details if allowed
        if ($includeSensitiveInfo) {
            if ($this->details) {
                $error['details'] = $this->details;
            }
            $error['stack_trace'] = $this->stackTrace;
        }

        // Include inner error recursively if detail level allows
        if ($this->innerError && $detailLevel >= 1) {
            $error['inner_error'] = $this->innerError->toApiResponse($detailLevel, $environment)['error'];
        }

        return ['error' => $error];
    }

}
