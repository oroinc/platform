<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all fields of an email template comply with the Twig sandbox security policy.
 *
 * Five distinct messages are defined — one per violation kind — so that
 * the reported error precisely names what is disallowed and where it was found.
 */
class EmailTemplateSecurityPolicy extends Constraint
{
    public const NOT_ALLOWED_TAG_ERROR = '0980d6f8-639d-46e8-a312-859b1e1377b9';
    public const NOT_ALLOWED_FILTER_ERROR = '5ffb1775-99b6-4e7b-a016-45e31ca732b0';
    public const NOT_ALLOWED_FUNCTION_ERROR = 'feeb05f7-b2ec-490c-822d-7dcd87938538';
    public const NOT_ALLOWED_PROPERTY_ERROR = '104740e8-2b2a-4165-aae4-6714bdc4bcec';
    public const NOT_ALLOWED_METHOD_ERROR = 'b5d5e3b2-5e87-4d60-b639-3a91d9ae641a';

    protected const ERROR_NAMES = [
        self::NOT_ALLOWED_TAG_ERROR => 'NOT_ALLOWED_TAG_ERROR',
        self::NOT_ALLOWED_FILTER_ERROR => 'NOT_ALLOWED_FILTER_ERROR',
        self::NOT_ALLOWED_FUNCTION_ERROR => 'NOT_ALLOWED_FUNCTION_ERROR',
        self::NOT_ALLOWED_PROPERTY_ERROR => 'NOT_ALLOWED_PROPERTY_ERROR',
        self::NOT_ALLOWED_METHOD_ERROR => 'NOT_ALLOWED_METHOD_ERROR',
    ];

    public string $tagMessage = 'oro.email.validator.security_policy.disallowed_tag';
    public string $filterMessage = 'oro.email.validator.security_policy.disallowed_filter';
    public string $functionMessage = 'oro.email.validator.security_policy.disallowed_function';
    public string $propertyMessage = 'oro.email.validator.security_policy.disallowed_property';
    public string $methodMessage = 'oro.email.validator.security_policy.disallowed_method';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
