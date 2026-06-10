<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a Twig sandbox violation caused by a disallowed function (e.g. dump, constant).
 * {@see getName()} returns the function name.
 * {@see getEntityClass()} always returns null - function violations are not entity-specific.
 */
class SecurityPolicyFunctionViolation extends AbstractSecurityPolicyViolation
{
    public function __construct(
        string $name,
        int $templateLine,
        \Throwable $cause,
    ) {
        parent::__construct(
            name: $name,
            variableName: null,
            entityClass: null,
            templateLine: $templateLine,
            cause: $cause
        );
    }
}
