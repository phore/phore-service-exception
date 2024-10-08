<?php

namespace Phore\ServiceException;

class ServiceExceptionKernel
{
    private string $serviceName;
    private string $environment;
    private string $traceId;
    private int $defaultHttpStatusCode;
    private int $detailLevel;

    public function __construct(
        string $serviceName,
        string $environment = 'production',
        ?string $traceId = null,
        int $defaultHttpStatusCode = 500,
        int $detailLevel = 0
    ) {
        $this->serviceName = $serviceName;
        $this->environment = $environment;
        $this->traceId = $traceId ?? $this->generateTraceId();
        $this->defaultHttpStatusCode = $defaultHttpStatusCode;
        $this->detailLevel = $detailLevel;
    }

    /**
     * Create a ServiceException with the given parameters.
     *
     * @param string                 $errorCode
     * @param string                 $message
     * @param int                    $httpStatusCode
     * @param array|null             $details
     * @param ServiceException|null  $innerError
     * @param string|null            $exceptionType
     *
     * @return ServiceException
     */
    public function createException(
        string $errorCode,
        string $message,
        int $httpStatusCode = null,
        ?array $details = null,
        ?ServiceException $innerError = null,
        ?string $exceptionType = null
    ): ServiceException {
        return new ServiceException(new T_ServiceException(
            message: $message,
            errorCode: $errorCode,
            service: $this->serviceName,
            exceptionType: $exceptionType,
            details: $details,
            innerError: $innerError,
            httpStatusCode: $httpStatusCode),
            $httpStatusCode ?? $this->defaultHttpStatusCode
        );
    }

    /**
     * Create a ServiceException from a Throwable.
     *
     * @param \Throwable $throwable
     * @param int|null  $httpStatusCode
     *
     * @return ServiceException
     */
    public function fromThrowable(\Throwable $throwable, ?int $httpStatusCode = null): ServiceException
    {
        return ServiceException::fromThrowable($throwable, $this->serviceName, $httpStatusCode ?? $this->defaultHttpStatusCode);
    }

    /**
     * Generate a unique trace ID.
     *
     * @return string
     */
    public function generateTraceId(): string
    {
        // Implement your preferred trace ID generation logic (e.g., UUID)
        return uniqid('', true);
    }

    /**
     * Convert a ServiceException to an API response.
     *
     * @param ServiceException $exception
     */
    public function toApiResponse(ServiceException $exception) : array {
        return $this->fromThrowable($exception)->toApiResponse($this->detailLevel, $this->environment);
    }


}
