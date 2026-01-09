<?php

namespace Oro\Component\MessageQueue\Log\Processor;

use Monolog\LogRecord;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * Adds information about the current consumer extension, message processor, message and job,
 * memory usage and time to the log record.
 */
class AddConsumerStateProcessor
{
    private ConsumerState $consumerState;

    private MessageToArrayConverterInterface $messageToArrayConverter;

    public function __construct(
        ConsumerState $consumerState,
        MessageToArrayConverterInterface $messageToArrayConverter
    ) {
        $this->consumerState = $consumerState;
        $this->messageToArrayConverter = $messageToArrayConverter;
    }

    /**
     * Adds message queue related information to the log record.
     *
     * @param LogRecord $record
     *
     * @return LogRecord
     */
    public function __invoke(LogRecord $record)
    {
        if ($this->consumerState->isConsumptionStarted()) {
            $extra = $record['extra'];
            $context = $record['context'];

            // add info about a consumption extension
            $extension = $this->consumerState->getExtension();
            if (null !== $extension) {
                $extra['extension'] = $this->getExtensionClass($extension);
            }
            // add info about a message and a message processor
            $message = $this->consumerState->getMessage();
            if (null !== $message) {
                $messageProcessorClass = $this->consumerState->getMessageProcessorClass();
                if ($messageProcessorClass !== '') {
                    $extra['processor'] = $messageProcessorClass;
                }
                $this->addMessageInfo($message, $extra);
            }
            // add info about a job
            $job = $this->consumerState->getJob();
            if (null !== $job) {
                $this->addJobInfo($job, $extra);
            }
            $this->addTimeInfo($extra);
            $this->addMemoryUsageInfo($extra);
            $this->moveMemoryUsageInfoFromContext($context, $extra, ['peak_memory', 'memory_taken']);

            return $record->with(extra: $extra, context: $context);
        }

        return $record;
    }

    /**
     * Add current memory usage
     */
    protected function addMemoryUsageInfo(array &$extra)
    {
        $memoryUsage = memory_get_usage();
        $this->consumerState->setPeakMemory($memoryUsage);
        $extra['memory_usage'] = BytesFormatter::format($memoryUsage);
    }

    /**
     * Move memory usage from context to extra parameters
     */
    protected function moveMemoryUsageInfoFromContext(array &$context, array &$extra, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($context[$key])) {
                $extra[$key] = $context[$key];
                unset($context[$key]);
            }
        }
    }

    /**
     * Add time passed since the consumer started processing message
     */
    protected function addTimeInfo(array &$extra)
    {
        if (null !== $this->consumerState->getStartTime()) {
            $time = (int)(microtime(true) * 1000) - $this->consumerState->getStartTime();
            $extra['elapsed_time'] = sprintf('%s ms', $time);
        }
    }

    protected function addMessageInfo(MessageInterface $message, array &$extra)
    {
        $items = $this->messageToArrayConverter->convert($message);
        foreach ($items as $key => $value) {
            $extra['message_' . $key] = $value;
        }
    }

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
}
