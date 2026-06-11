<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation;

/**
 * Represents a single Twig sandbox security policy violation.
 *
 * A violation can be of five kinds - tag, filter, function, property, or method -
 * each represented by a distinct concrete class extending {@see AbstractSecurityPolicyViolation}.
 */
interface SecurityPolicyViolationInterface
{
    /**
     * Returns the name of the disallowed element:
     * tag name, filter name, function name, property name, or method name.
     */
    public function getName(): string;

    /**
     * Returns the name of the Twig template variable or expression identifier on which this access was performed.
     *
     * For direct accesses on named template variables, this is the variable name (e.g. 'entity' for entity.avatar).
     * For nested/chained expressions, this value may contain an intermediate attribute name or array index.
     * Returns null for tag, filter, and function violations.
     */
    public function getVariableName(): ?string;

    /**
     * Returns the FQCN of the entity on which the disallowed property or method was accessed.
     * Returns null for tag, filter, and function violations (not entity-specific).
     */
    public function getEntityClass(): ?string;

    /**
     * Returns the line number in the template source where the violation was detected.
     * Returns -1 when the line number cannot be determined
     * (e.g. for tag/filter/function violations whose Twig exceptions carry no line info).
     */
    public function getTemplateLine(): int;

    /**
     * Returns the original Twig exception that triggered this violation.
     */
    public function getCause(): \Throwable;
}
