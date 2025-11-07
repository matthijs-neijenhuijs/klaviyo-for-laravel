<?php

namespace DutchBridge\KlaviyoForLaravel\Exceptions;

class KlaviyoIdentityException extends KlaviyoException
{
    public static function invalidIdentity(array $requiredFields): self
    {
        return new self(
            sprintf(
                'Identity must contain one of the following fields: %s. Troubleshooting: Ensure your user model implements KlaviyoIdentity contract or pass a valid identity array/string.',
                implode(', ', $requiredFields)
            )
        );
    }

    public static function emptyIdentity(): self
    {
        return new self(
            'Identity cannot be empty. Troubleshooting: Make sure to provide an email, phone_number, external_id, or _kx value.'
        );
    }
}