<?php

namespace DutchBridge\KlaviyoForLaravel\Exceptions;

class KlaviyoApiException extends KlaviyoException
{
    protected int $statusCode;
    protected array $responseData;

    public function __construct(string $message, int $statusCode, array $responseData = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public static function fromResponse(int $statusCode, array $responseData): self
    {
        $message = match($statusCode) {
            400 => 'Bad Request: Invalid request data sent to Klaviyo API',
            401 => 'Unauthorized: Invalid API key or insufficient permissions',
            403 => 'Forbidden: API key does not have access to this resource',
            404 => 'Not Found: Resource not found in Klaviyo',
            409 => 'Conflict: Resource already exists or conflict in request',
            429 => 'Rate Limited: Too many requests sent to Klaviyo API',
            500 => 'Internal Server Error: Klaviyo API encountered an error',
            502, 503, 504 => 'Service Unavailable: Klaviyo API is temporarily unavailable',
            default => 'Klaviyo API Error: Unexpected response received',
        };

        if (isset($responseData['errors'])) {
            $errors = collect($responseData['errors'])
                ->pluck('detail')
                ->filter()
                ->implode('; ');
            
            if ($errors) {
                $message .= ': ' . $errors;
            }
        }

        return new self($message, $statusCode, $responseData);
    }
}