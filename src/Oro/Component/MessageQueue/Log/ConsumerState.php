<?php

namespace Oro\Component\MessageQueue\Log;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * An instance of this class is used to store an information about
 * the current state of message consumer.
 */
class ConsumerState
{
    /** @var bool */
    private $enabled = false;

    /** @var ExtensionInterface */
    private $extension;

    /** @var MessageProcessorInterface */
    private $messageProcessor;

    /** @var MessageInterface */
    private $message;

    /** @var Job */
    private $job;

    /** @var integer */
    private $startTime;

    /** @var integer */
    private $peakMemory;

    /** @var integer */
    private $startMemoryUsage;

    /**
     * Indicates whether the consumption is started.
     *
     * @return bool
     */
    public function isConsumptionStarted()
    {
        return $this->enabled;
    }

    /**
     * This method should be called when the consumption is started.
     */
    public function startConsumption()
    {
        $this->enabled = true;
    }

    /**
     * This method should be called when the consumption is finished.
     */
    public function stopConsumption()
    {
        $this->enabled = false;
    }

    /**
     * Returns a consumption extension that is executed at the moment.
     *
     * @return ExtensionInterface|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Sets a consumption extension that is executed at the moment.
     *
     * @param ExtensionInterface|null $extension
     */
    public function setExtension(ExtensionInterface $extension = null)
    {
        $this->extension = $extension;
    }

    /**
     * Returns a message processor that is executed at the moment.
     *
     * @return MessageProcessorInterface|null
     */
    public function getMessageProcessor()
    {
        return $this->messageProcessor;
    }

    /**
     * Sets a message processor that is executed at the moment.
     *
     * @param MessageProcessorInterface|null $messageProcessor
     */
    public function setMessageProcessor(MessageProcessorInterface $messageProcessor = null)
    {
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * Returns a message that is processed at the moment.
     *
     * @return MessageInterface|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets a message that is processed at the moment.
     *
     * @param MessageInterface|null $message
     */
    public function setMessage(MessageInterface $message = null)
    {
        $this->message = $message;
        $this->startTime = (int)(microtime(true) * 1000);
        $this->startMemoryUsage = memory_get_usage();
        $this->peakMemory = $this->startMemoryUsage;
    }

    /**
     * Returns a job that is executed at the moment.
     *
     * @return Job|null
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Sets a job that is executed at the moment.
     *
     * @param Job $job
     */
    public function setJob(Job $job = null)
    {
        $this->job = $job;
    }

    /**
     * @return integer
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getPeakMemory()
    {
        return $this->peakMemory;
    }

    /**
     * @return int
     */
    public function getStartMemoryUsage()
    {
        return $this->startMemoryUsage;
    }

    /**
     * @param int $peakMemory
     */
    public function setPeakMemory($peakMemory)
    {
        if ($this->peakMemory < $peakMemory) {
            $this->peakMemory = $peakMemory;
        }
    }

    /**
     * Removes all data excluding the consumption state from this object.
     */
    public function clear()
    {
        $this->extension = null;
        $this->messageProcessor = null;
        $this->message = null;
        $this->job = null;
        $this->startTime = null;
        $this->startMemoryUsage = 0;
        $this->peakMemory = 0;
    }
}
