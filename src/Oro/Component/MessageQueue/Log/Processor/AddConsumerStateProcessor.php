<?php

namespace Oro\Component\MessageQueue\Log\Processor;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * Adds information about the current consumer extension, message processor, message and job,
 * memory usage and time to the log record.
 */
class AddConsumerStateProcessor
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var MessageToArrayConverterInterface */
    private $messageToArrayConverter;

    /**
     * @param ConsumerState $consumerState
     * @param MessageProcessorClassProvider $messageProcessorClassProvider
     * @param MessageToArrayConverterInterface $messageToArrayConverter
     */
    public function __construct(
        ConsumerState $consumerState,
        MessageProcessorClassProvider $messageProcessorClassProvider,
        MessageToArrayConverterInterface $messageToArrayConverter
    ) {
        $this->consumerState = $consumerState;
        $this->messageProcessorClassProvider = $messageProcessorClassProvider;
        $this->messageToArrayConverter = $messageToArrayConverter;
    }

    /**
     * Adds message queue related information to the log record.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if ($this->consumerState->isConsumptionStarted()) {
            // add info about a consumption extension
            $extension = $this->consumerState->getExtension();
            if (null !== $extension) {
                $record['extra']['extension'] = $this->getExtensionClass($extension);
            }
            // add info about a message and a message processor
            $message = $this->consumerState->getMessage();
            if (null !== $message) {
                $messageProcessor = $this->consumerState->getMessageProcessor();
                if (null !== $messageProcessor) {
                    $record['extra']['processor'] = $this->getMessageProcessorClass(
                        $messageProcessor,
                        $message
                    );
                }
                $this->addMessageInfo($message, $record['extra']);
            }
            // add info about a job
            $job = $this->consumerState->getJob();
            if (null !== $job) {
                $this->addJobInfo($job, $record['extra']);
            }
            $this->addTimeInfo($record['extra']);
            $this->addMemoryUsageInfo($record['extra']);
            $this->moveMemoryUsageInfoFromContext($record, ['peak_memory', 'memory_taken']);
        }

        return $record;
    }

    /**
     * Add current memory usage
     *
     * @param array $extra
     */
    protected function addMemoryUsageInfo(array &$extra)
    {
        $memoryUsage = memory_get_usage();
        $this->consumerState->setPeakMemory($memoryUsage);
        $extra['memory_usage'] = BytesFormatter::format($memoryUsage);
    }

    /**
     * Move memory usage from context to extra parameters
     *
     * @param array $record
     * @param array $keys
     */
    protected function moveMemoryUsageInfoFromContext(array &$record, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($record['context'][$key])) {
                $record['extra'][$key] = $record['context'][$key];
                unset($record['context'][$key]);
            }
        }
    }

    /**
     * Add time passed since the consumer started processing message
     *
     * @param array $extra
     */
    protected function addTimeInfo(array &$extra)
    {
        if (null !== $this->consumerState->getStartTime()) {
            $time = (int)(microtime(true) * 1000) - $this->consumerState->getStartTime();
            $extra['elapsed_time'] = sprintf('%s ms', $time);
        }
    }

    /**
     * @param MessageInterface $message
     * @param array            $extra
     */
    protected function addMessageInfo(MessageInterface $message, array &$extra)
    {
        $items = $this->messageToArrayConverter->convert($message);
        foreach ($items as $key => $value) {
            $extra['message_' . $key] = $value;
        }
    }

    /**
     * @param Job   $job
     * @param array $extra
     */
    protected function addJobInfo(Job $job, array &$extra)
    {
        $extra['job_id'] = $job->getId();
        $extra['job_name'] = $job->getName();
        $extra['job_data'] = $job->getData();
    }

    /**
     * Gets the class name of the given consumption extension.
     *
     * @param ExtensionInterface $extension
     *
     * @return string
     */
    protected function getExtensionClass(ExtensionInterface $extension)
    {
        if ($extension instanceof ValueHolderInterface) {
            $extension = $extension->getWrappedValueHolderValue();
        }

        return get_class($extension);
    }

    /**
     * Gets the class name of the given message processor.
     *
     * @param MessageProcessorInterface $messageProcessor
     * @param MessageInterface          $message
     *
     * @return string
     */
    protected function getMessageProcessorClass(MessageProcessorInterface $messageProcessor, MessageInterface $message)
    {
        return $this->messageProcessorClassProvider->getMessageProcessorClass($messageProcessor, $message);
    }
}
