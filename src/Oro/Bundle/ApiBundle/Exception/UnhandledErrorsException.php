<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Oro\Bundle\ApiBundle\Model\Error;

/**
 * This exception is thrown when execution of an action finished and its Context has at least one error.
 */
class UnhandledErrorsException extends RuntimeException
{
    /** @var Error[] */
    private $errors;

    /**
     * @param Error[] $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('Unhandled error(s) occurred.');
        $this->errors = $errors;
    }

    /**
     * Returns a list of unhandled errors.
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
