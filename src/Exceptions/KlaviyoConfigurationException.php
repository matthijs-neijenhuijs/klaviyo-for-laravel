<?php

namespace DutchBridge\KlaviyoForLaravel\Exceptions;

class KlaviyoConfigurationException extends KlaviyoException
{
    public static function missingApiKey(): self
    {
        return new self(
            'Klaviyo API key is not configured. Please set KLAVIYO_PRIVATE_API_KEY in your environment.'
        );
    }

    public static function invalidApiKey(): self
    {
        return new self(
            'Klaviyo API key is invalid. Please check your KLAVIYO_PRIVATE_API_KEY configuration.'
        );
    }

    public static function missingApiVersion(): self
    {
        return new self(
            'Klaviyo API version is not configured. Please set KLAVIYO_API_VERSION in your environment.'
        );
    }

    public static function invalidIdentityKeyName(): self
    {
        return new self(
            'Klaviyo identity key name is invalid. Please check your KLAVIYO_IDENTITY_KEY_NAME configuration.'
        );
    }
}