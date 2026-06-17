<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a Twig sandbox violation caused by accessing a disallowed property on an entity.
 * {@see getName()} returns the property name.
 * {@see getEntityClass()} returns the FQCN of the entity class.
 * {@see getTemplateLine()} returns the line in the template where the access was found.
 */
class SecurityPolicyPropertyViolation extends AbstractSecurityPolicyViolation
{
}
