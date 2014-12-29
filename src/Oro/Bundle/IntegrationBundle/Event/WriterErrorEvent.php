<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WriterErrorEvent extends Event
{
    const NAME = 'oro_integration.writer_error';

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

    /**
     * @param \Exception $exception
     */
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
