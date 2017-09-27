<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Processor;

use ProxyManager\Proxy\ValueHolderInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Bundle\MessageQueueBundle\Log\Converter\MessageToArrayConverterInterface;
use Oro\Bundle\MessageQueueBundle\Log\MessageProcessorClassProvider;

/**
 * Adds information about the current consumer extension, message processor, message and job to the log record.
 */
class AddConsumerStateProcessor
{
    /** @var ContainerInterface */
    private $container;

    /** @var ConsumerState */
    private $consumerState;

    /** @var MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var MessageToArrayConverterInterface */
    private $messageToArrayConverter;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $consumerState = $this->getConsumerState();
        if ($consumerState->isConsumptionStarted()) {
            // add info about a consumption extension
            $extension = $consumerState->getExtension();
            if (null !== $extension) {
                $record['extra']['extension'] = $this->getExtensionClass($extension);
            }
            // add info about a message and a message processor
            $message = $consumerState->getMessage();
            if (null !== $message) {
                $messageProcessor = $consumerState->getMessageProcessor();
                if (null !== $messageProcessor) {
                    $record['extra']['processor'] = $this->getMessageProcessorClass(
                        $messageProcessor,
                        $message
                    );
                }
                $this->addMessageInfo($message, $record['extra']);
            }
            // add info about a job
            $job = $consumerState->getJob();
            if (null !== $job) {
                $this->addJobInfo($job, $record['extra']);
            }
        }

        return $record;
    }

    /**
     * @return ConsumerState
     */
    protected function getConsumerState()
    {
        if (null === $this->consumerState) {
            $this->consumerState = $this->container->get('oro_message_queue.log.consumer_state');
        }

        return $this->consumerState;
    }

    /**
     * @return MessageProcessorClassProvider
     */
    protected function getMessageProcessorClassProvider()
    {
        if (null === $this->messageProcessorClassProvider) {
            $this->messageProcessorClassProvider = $this->container
                ->get('oro_message_queue.log.message_processor_class_provider');
        }

        return $this->messageProcessorClassProvider;
    }

    /**
     * @return MessageToArrayConverterInterface
     */
    protected function getMessageToArrayConverter()
    {
        if (null === $this->messageToArrayConverter) {
            $this->messageToArrayConverter = $this->container
                ->get('oro_message_queue.log.message_to_array_converter');
        }

        return $this->messageToArrayConverter;
    }

    /**
     * @param MessageInterface $message
     * @param array            $extra
     */
    protected function addMessageInfo(MessageInterface $message, array &$extra)
    {
        $items = $this->getMessageToArrayConverter()->convert($message);
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
        return $this->getMessageProcessorClassProvider()->getMessageProcessorClass($messageProcessor, $message);
    }
}
