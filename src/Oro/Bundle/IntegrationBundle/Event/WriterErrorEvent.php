<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when an error occurs during import/export write operations.
 *
 * This event carries information about the batch items being processed, the job name,
 * the exception that occurred, and any warning messages. Listeners can use this event
 * to handle errors, log them, or determine whether the error can be skipped and processing
 * should continue.
 */
class WriterErrorEvent extends Event
{
    public const NAME = 'oro_integration.writer_error';

    /** @var array */
    protected $batchItems;

    /** @var string */
    protected $jobName;

    /** @var \Exception */
    protected $exception;

    /** @var string */
    protected $warning = '';

    /** @var bool */
    protected $couldBeSkipped;

    /**
     * @param array      $batchItems
     * @param string     $jobName
     * @param \Exception $exception
     */
    public function __construct(array $batchItems, $jobName, \Exception $exception)
    {
        $this->batchItems = $batchItems;
        $this->jobName    = $jobName;
        $this->exception  = $exception;
        $this->warning    = $exception->getMessage() . PHP_EOL;
    }

    /**
     * @return array
     */
    public function getBatchItems()
    {
        return $this->batchItems;
    }

    /**
     * @param boolean $couldBeSkipped
     */
    public function setCouldBeSkipped($couldBeSkipped)
    {
        $this->couldBeSkipped = $couldBeSkipped;
    }

    /**
     * @return boolean
     */
    public function getCouldBeSkipped()
    {
        return $this->couldBeSkipped;
    }

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @param string $warning
     */
    public function addWarningText($warning)
    {
        $this->warning .= $warning;
    }

    /**
     * @return string
     */
    public function getWarning()
    {
        return $this->warning;
    }
}
