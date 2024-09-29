<?php

namespace Phore\ServiceException;

class T_ServiceException
{
    public function __construct(

        public string $message,
        public string $errorCode,
        public ?string $service = null,
        public ?string $timestamp = null,
        public ?string $traceId = null,
        public ?string $exceptionType = null,
        public ?array $details = null,
        public ?T_ServiceException $innerError = null,
        public ?array $stackTrace = null,
        public ?int $httpStatusCode = null


    ){

        $this->timestamp = $timestamp ?? (new \DateTime())->format('c');
    }

    /**
     * @param array $data
     * @param bool $strict  If true, throw exception if required fields are missing, else null is returned
     * @return T_ServiceException|null
     */
    public static function fromArray (array $data, bool $strict = false) : ?T_ServiceException
    {
        // Check required fields
        if (! isset ($data["message"]) || ! is_string($data["message"])) {
            if ($strict) {
                throw new \InvalidArgumentException("Required field 'message' not set.");
            }
            return null;
        }
        if (! isset ($data["code"]) || ! is_string($data["code"])) {
            if ($strict) {
                throw new \InvalidArgumentException("Required field 'code' not set.");
            }
            return null;
        }

        // Create and return the T_ServiceException
        return new T_ServiceException(
            message: $data["message"],
            errorCode: $data["code"],
            service: $data["service"] ?? null,
            timestamp: $data["timestamp"] ?? null,
            traceId: $data["traceId"] ?? null,
            exceptionType: $data["exception_type"] ?? null,
            details: $data["details"] ?? null,
            innerError: isset($data["inner_error"]) ? T_ServiceException::fromArray($data["inner_error"]) : null,
            stackTrace: $data["stack_trace"] ?? null,
            httpStatusCode: $data["http_status_code"] ?? null
        );
    }

    public function getRootCause() : T_ServiceException
    {
        $ex = $this;
        while ($ex->innerError !== null) {
            $ex = $ex->innerError;
        }
        return $ex;
    }

    /**
     * @return string[]
     */
    public function getAllMessages() : array
    {
        $ret = [];
        $ex = $this;
        while ($ex !== null) {
            $ret[] = $ex->message;
            $ex = $ex->innerError;
        }
        return $ret;
    }


    public function toArray() : array
    {
        $ret = [
            "message" => $this->message,
            "code" => $this->errorCode,
            "service" => $this->service,
            "timestamp" => $this->timestamp,
            "traceId" => $this->traceId,
            "exception_type" => $this->exceptionType,
            "details" => $this->details,
            "inner_error" => $this->innerError ? $this->innerError->toArray() : null,
            "stack_trace" => $this->stackTrace,
            "http_status_code" => $this->httpStatusCode
        ];
        return $ret;
    }

    public function toApiResponse(int $detailLevel = 0, string $environment = 'production'): array
    {
        // Determine whether to include sensitive information
        $includeSensitiveInfo = ($environment !== 'production') && ($detailLevel > 0);

        // Base error structure
        $error = [
            'code' => $this->errorCode,
            'message' => $this->message,
            'service' => $this->service,
            'http_status_code' => $this->httpStatusCode,
        ];

        // Include additional details based on detail level
        if ($detailLevel >= 1) {
            $error['timestamp'] = $this->timestamp;
            $error['trace_id'] = $this->traceId;
            $error['exception_type'] = $this->exceptionType;
        }
        if ($this->details) {
            $error['details'] = $this->details;
        }
        // Include sensitive details if allowed
        if ($includeSensitiveInfo) {
            $error['stack_trace'] = $this->stackTrace;

        }
        if ($this->innerError && $detailLevel >= 1) {
            $error['inner_error'] = $this->innerError->toApiResponse($detailLevel, $environment)['error'];
        }


        return ['error' => $error];
    }


}
