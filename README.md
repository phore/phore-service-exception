

ServiceException and ServiceExceptionKernel

A PHP 8 library for standardized exception handling in microservices architectures. This library provides:

    ServiceException: A custom exception class that encapsulates error information in a consistent, serializable format, suitable for API responses and inter-service communication.
    ServiceExceptionKernel: A helper class that centralizes exception creation, handling, and contextual information like service names and trace IDs.

Table of Contents

    Features
    Installation
    Usage
        Initialization
        Creating and Throwing Exceptions
        Catching and Handling Exceptions
        Generating API Responses
        Logging Exceptions
    Classes
        ServiceException
            Properties
            Methods
        ServiceExceptionKernel
            Methods
    Best Practices
        Handling Sensitive Information
        Environment Configuration
    Examples
        Example 1: Basic Usage
        Example 2: Handling Nested Exceptions
        Example 3: Custom Exception Types
    Contributing
    License

Features

    Standardized error format for API responses.
    Supports nested exceptions (inner_error) for detailed error chains.
    Includes HTTP status codes for appropriate client responses.
    Controls the level of detail in error responses based on environment and detail level.
    Manages sensitive information to prevent exposure in production environments.
    Facilitates distributed tracing with trace IDs.
    Simplifies exception handling with the ServiceExceptionKernel.

Installation

Use Composer to install the library (assuming it's available via Composer):

bash

composer require yournamespace/service-exception

Usage
Initialization

At the entry point of your application (e.g., in a middleware or base controller), initialize the ServiceExceptionKernel:

php

use YourNamespace\ServiceExceptionKernel;

$environment = getenv('APP_ENV') ?: 'production';
$serviceName = 'UserService';
$traceId = $_SERVER['HTTP_X_TRACE_ID'] ?? null;

$exceptionKernel = new ServiceExceptionKernel(
serviceName: $serviceName,
environment: $environment,
traceId: $traceId,
defaultHttpStatusCode: 500,
detailLevel: ($environment === 'production') ? 0 : 2
);

Creating and Throwing Exceptions

Use the ServiceExceptionKernel to create and throw exceptions consistently:

php

try {
// Some code that may throw an exception
$user = $userService->getUserById($userId);
if (!$user) {
throw $exceptionKernel->createException(
errorCode: 'USER_NOT_FOUND',
message: "User with ID {$userId} not found.",
httpStatusCode: 404,
details: ['userId' => $userId]
);
}
} catch (Throwable $e) {
// Handle the exception
}

Catching and Handling Exceptions

Catch exceptions and convert them using the kernel:

php

try {
// Code that may throw exceptions
} catch (Throwable $e) {
$serviceException = $exceptionKernel->handleException($e);
// Further handling...
}

Generating API Responses

Use the toApiResponse method of ServiceException to generate standardized error responses:

php

$responseData = $serviceException->toApiResponse(
detailLevel: $exceptionKernel->getDetailLevel(),
environment: $exceptionKernel->getEnvironment()
);

// Return HTTP response (using your framework's response class)
return new JsonResponse($responseData, $serviceException->getHttpStatusCode());

Logging Exceptions

Log exceptions using a PSR-3 compliant logger:

php

use Psr\Log\LoggerInterface;

// Assuming $logger is an instance of LoggerInterface
$exceptionKernel->logException($serviceException, $logger);

Classes
ServiceException

A custom exception class that extends PHP's Exception and implements JsonSerializable.
Properties

    errorCode (string): Custom error code.
    message (string): Error message.
    service (string): Name of the service where the error occurred.
    httpStatusCode (int): HTTP status code associated with the error.
    timestamp (string): Timestamp when the error occurred (ISO 8601 format).
    traceId (string|null): Trace ID for distributed tracing.
    exceptionType (string|null): Type of the exception.
    details (array|null): Additional error details.
    innerError (ServiceException|null): Nested inner exception.
    stackTrace (array): Stack trace of the exception.

Methods

    __construct(...): Initializes a new instance of ServiceException.
    fromJson(string $json, bool $strict = false): ?ServiceException: Creates an instance from a JSON string.
    fromArray(array $data, bool $strict = false): ?ServiceException: Creates an instance from an array.
    fromException(Exception|\Error $error, string $service, int $httpStatusCode = 500): ServiceException: Creates an instance from an existing exception.
    jsonSerialize(): array: Specifies data for JSON serialization.
    __toString(): string: Returns a string representation of the exception.
    toApiResponse(int $detailLevel = 0, string $environment = 'production'): array: Converts the exception to a format suitable for API responses.
    getters: Various getters for accessing properties.

ServiceExceptionKernel

A helper class that centralizes exception creation, handling, and contextual information.
Methods

    __construct(...): Initializes the kernel with service name, environment, etc.
    createException(...): Creates a ServiceException with provided information.
    fromThrowable(Throwable $throwable, ?int $httpStatusCode = null): ServiceException: Converts any Throwable into a ServiceException.
    handleException(Throwable $throwable): ServiceException: Handles an exception and converts it.
    logException(ServiceException $exception, LoggerInterface $logger): void: Logs the exception.
    generateTraceId(): string: Generates a unique trace ID.
    getters and setters: Access and modify properties like trace ID, environment, detail level, etc.

Best Practices
Handling Sensitive Information

    Production Environment: Use minimal detail level (0) to avoid exposing sensitive information.
    Development Environment: Higher detail levels (1 or 2) can be used to aid debugging.
    Redaction: Implement logic to redact or exclude sensitive fields from error responses.
    Logging: Log full exception details internally for debugging purposes.

Environment Configuration

    Environment Variables: Use environment variables to set the application environment (APP_ENV).
    Central Configuration: Configure default HTTP status codes and detail levels in one place.

Examples
Example 1: Basic Usage

php

use YourNamespace\ServiceExceptionKernel;

$exceptionKernel = new ServiceExceptionKernel('UserService', 'production');

try {
// Some code that throws an exception
throw new \RuntimeException('An unexpected error occurred.');
} catch (Throwable $e) {
$serviceException = $exceptionKernel->handleException($e);
$responseData = $serviceException->toApiResponse(
detailLevel: $exceptionKernel->getDetailLevel(),
environment: $exceptionKernel->getEnvironment()
);
return new JsonResponse($responseData, $serviceException->getHttpStatusCode());
}

Example 2: Handling Nested Exceptions

php

use YourNamespace\ServiceExceptionKernel;

$exceptionKernel = new ServiceExceptionKernel('OrderService', 'development');

try {
// Code that may throw exceptions
try {
// Some code that throws an exception
throw new \InvalidArgumentException('Invalid order ID.');
} catch (\InvalidArgumentException $e) {
throw $exceptionKernel->createException(
errorCode: 'INVALID_ORDER_ID',
message: 'The provided order ID is invalid.',
httpStatusCode: 400,
exceptionType: 'InvalidOrderIdException',
innerError: $exceptionKernel->fromThrowable($e)
);
}
} catch (Throwable $e) {
$serviceException = $exceptionKernel->handleException($e);
$responseData = $serviceException->toApiResponse(
detailLevel: $exceptionKernel->getDetailLevel(),
environment: $exceptionKernel->getEnvironment()
);
return new JsonResponse($responseData, $serviceException->getHttpStatusCode());
}

Example 3: Custom Exception Types

php

use YourNamespace\ServiceException;

class NotFoundException extends ServiceException
{
public function __construct(
string $errorCode,
string $message,
string $service,
?string $traceId = null,
?array $details = null,
?ServiceException $innerError = null
) {
parent::__construct(
errorCode: $errorCode,
message: $message,
service: $service,
httpStatusCode: 404,
traceId: $traceId,
exceptionType: 'NotFoundException',
details: $details,
innerError: $innerError
);
}
}

// Usage
throw new NotFoundException(
errorCode: 'USER_NOT_FOUND',
message: 'The user with ID 12345 was not found.',
service: 'UserService',
details: ['userId' => 12345]
);

Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss improvements, bug fixes, or new features.
License

This project is licensed under the MIT License. See the LICENSE file for details.

Note: Replace YourNamespace with the appropriate namespace for your project. Ensure that all dependencies are properly installed and autoloaded via Composer.

Feel free to reach out if you have any questions or need further assistance!
