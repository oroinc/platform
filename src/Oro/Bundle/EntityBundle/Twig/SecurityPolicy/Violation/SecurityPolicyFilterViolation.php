<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a Twig sandbox violation caused by a disallowed filter (e.g. raw, join).
 * {@see getName()} returns the filter name.
 * {@see getEntityClass()} always returns null - filter violations are not entity-specific.
 */
class SecurityPolicyFilterViolation extends AbstractSecurityPolicyViolation
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
