<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

/**
 * This interface can be implemented by a validation constraint in case if a status code
 * different than 400 (Bad Request) should be returned if the constraint is not satisfied.
 */
interface ConstraintWithStatusCodeInterface
{
    /**
     * Returns HTTP status code that should be returned if the constraint is not satisfied.
     *
     * @return int
     */
    public function getStatusCode();
}
