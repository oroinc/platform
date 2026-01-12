<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Encapsulates the result of an import/export job execution.
 *
 * This class holds the outcome of a job including success status, job ID and code,
 * the execution context with operation counters, any failure exceptions that occurred,
 * and a flag indicating whether the job needs redelivery. It provides a fluent interface
 * for building the result object.
 */
class JobResult
{
    /**
     * @var boolean
     */
    protected $successful;

    /**
     * @var int
     */
    protected $jobId;

    /**
     * @var string
     */
    protected $jobCode;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var array
     */
    protected $failureExceptions = array();

    /**
     * @var bool
     */
    protected $needRedelivery;

    /**
     * @return ContextInterface|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getFailureExceptions()
    {
        return $this->failureExceptions;
    }

    /**
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @param boolean $successful
     *
     * @return JobResult
     */
    public function setSuccessful($successful)
    {
        $this->successful = $successful;
        return $this;
    }

    /**
     * @return bool
     */
    public function needRedelivery()
    {
        return $this->needRedelivery;
    }

    /**
     * @param bool $needRedelivery
     *
     * @return JobResult
     */
    public function setNeedRedelivery(bool $needRedelivery)
    {
        $this->needRedelivery = $needRedelivery;

        return $this;
    }

    /**
     * @param string $failureException
     *
     * @return JobResult
     */
    public function addFailureException($failureException)
    {
        $this->failureExceptions[] = $failureException;
        return $this;
    }

    /**
     * @param array $exceptions
     *
     * @return $this
     */
    public function setFailureExceptions(array $exceptions)
    {
        $this->failureExceptions = $exceptions;
        return $this;
    }

    /**
     * @param ContextInterface $context
     *
     * @return JobResult
     */
    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param int $jobId
     *
     * @return JobResult
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param string $jobCode
     *
     * @return JobResult
     */
    public function setJobCode($jobCode)
    {
        $this->jobCode = $jobCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobCode()
    {
        return $this->jobCode;
    }
}
