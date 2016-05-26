<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Session as TransportSession;

class DelegateMessageProcessor implements MessageProcessor
{
    /**
     * @var MessageProcessorRegistryInterface
     */
    protected $registry;

    /**
     * @param MessageProcessorRegistryInterface $registry
     */
    public function __construct(MessageProcessorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, TransportSession $session)
    {
        $processorName = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (false == $processorName) {
            throw new \LogicException(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_PROCESSOR_NAME
            ));
        }

        return $this->registry->get($processorName)->process($message, $session);
    }
}
