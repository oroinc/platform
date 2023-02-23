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
    private bool $softErrorsHandling = false;
    /** @var Error[]|null */
    private ?array $errors = null;

    /**
     * Whether any error occurred when processing an action.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Gets all errors occurred when processing an action.
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * Registers an error.
     */
    public function addError(Error $error): void
    {
        if (null === $this->errors) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * Removes all errors.
     */
    public function resetErrors(): void
    {
        $this->errors = null;
    }

    /**
     * Gets a value indicates whether errors and exceptions should just stop processing
     * or an exception should be thrown is any error or exception occurred.
     */
    public function isSoftErrorsHandling(): bool
    {
        return $this->softErrorsHandling;
    }

    /**
     * Sets a value indicates whether errors and exceptions should just stop processing
     * or an exception should be thrown is any error or exception occurred.
     */
    public function setSoftErrorsHandling(bool $softErrorsHandling): void
    {
        $this->softErrorsHandling = $softErrorsHandling;
    }
}
