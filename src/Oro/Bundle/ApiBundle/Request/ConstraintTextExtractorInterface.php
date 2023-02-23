<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\Validator;

/**
 * Provides an interface for classes that extracts information from a validation constraint object.
 */
interface ConstraintTextExtractorInterface
{
    /**
     * Returns the HTTP status code applicable to a given Constraint object.
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint): ?int;

    /**
     * Returns an application-specific error code for a given Constraint object.
     */
    public function getConstraintCode(Validator\Constraint $constraint): ?string;

    /**
     * Returns a human-readable representation of the type of a given Constraint object.
     */
    public function getConstraintType(Validator\Constraint $constraint): ?string;
}
