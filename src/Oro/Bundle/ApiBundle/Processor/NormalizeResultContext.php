<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Model\Error;

/**
 * The base execution context for processors for actions with "normalize_result" group.
 * Processors from this group are intended to prepare a valid response
 * and they are executed regardless whether an error occurred or not.
 */
class NormalizeResultContext extends ApiContext
{
    /** bool */
    private $softErrorsHandling = false;

    /** @var Error[] */
    private $errors;

    /**
     * Whether any error occurred when processing an action.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Gets all errors occurred when processing an action.
     *
     * @return Error[]
     */
    public function getErrors()
    {
        if (null === $this->errors) {
            return [];
        }

        return $this->errors;
    }

    /**
     * Registers an error.
     */
    public function addError(Error $error)
    {
        if (null === $this->errors) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * Removes all errors.
     */
    public function resetErrors()
    {
        $this->errors = null;
    }

    /**
     * Gets a value indicates whether errors and exceptions should just stop processing
     * or an exception should be thrown is any error or exception occurred.
     *
     * @return bool
     */
    public function isSoftErrorsHandling()
    {
        return $this->softErrorsHandling;
    }

    /**
     * Sets a value indicates whether errors and exceptions should just stop processing
     * or an exception should be thrown is any error or exception occurred.
     *
     * @param bool $softErrorsHandling
     */
    public function setSoftErrorsHandling($softErrorsHandling)
    {
        $this->softErrorsHandling = $softErrorsHandling;
    }
}
