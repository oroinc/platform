<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a Twig sandbox violation caused by a disallowed tag (e.g. {% block %}, {% macro %}).
 * {@see getName()} returns the tag name.
 * {@see getEntityClass()} always returns null - tag violations are not entity-specific.
 */
class SecurityPolicyTagViolation extends AbstractSecurityPolicyViolation
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
