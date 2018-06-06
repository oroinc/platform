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
     *
     * @param Validator\Constraint $constraint
     *
     * @return int|null
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint);

    /**
     * Returns an application-specific error code for a given Constraint object.
     *
     * @param Validator\Constraint $constraint
     *
     * @return string|null
     */
    public function getConstraintCode(Validator\Constraint $constraint);

    /**
     * Returns a human-readable representation of the type of a given Constraint object.
     *
     * @param Validator\Constraint $constraint
     *
     * @return string|null
     */
    public function getConstraintType(Validator\Constraint $constraint);
}
