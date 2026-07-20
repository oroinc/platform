<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a Twig sandbox violation caused by calling a disallowed method on an entity.
 * {@see getName()} returns the method name.
 * {@see getEntityClass()} returns the FQCN of the entity class.
 * {@see getTemplateLine()} returns the line in the template where the call was found.
 */
class SecurityPolicyMethodViolation extends AbstractSecurityPolicyViolation
{
}
