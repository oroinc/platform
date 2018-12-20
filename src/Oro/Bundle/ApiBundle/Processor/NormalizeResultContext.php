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
    /**
     * a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred
     */
    const SOFT_ERRORS_HANDLING = 'softErrorsHandling';

    /** @var Error[] */
    private $errors;

    /**
     * Whether any error happened during the processing of an action.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Gets all errors happened during the processing of an action.
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
     *
     * @param Error $error
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
     * Gets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     *
     * @return bool
     */
    public function isSoftErrorsHandling()
    {
        return (bool)$this->get(self::SOFT_ERRORS_HANDLING);
    }

    /**
     * Sets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     *
     * @param bool $softErrorsHandling
     */
    public function setSoftErrorsHandling($softErrorsHandling)
    {
        if ($softErrorsHandling) {
            $this->set(self::SOFT_ERRORS_HANDLING, true);
        } else {
            $this->remove(self::SOFT_ERRORS_HANDLING);
        }
    }
}
