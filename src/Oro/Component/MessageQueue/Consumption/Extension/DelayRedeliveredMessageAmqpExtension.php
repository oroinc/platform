<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;

class DelayRedeliveredMessageAmqpExtension implements Extension
{
    use ExtensionTrait;

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context)
    {
        /** @var AmqpSession $session */
        $session = $context->getSession();
        if (false == $session instanceof  AmqpSession) {
            return;
        }

        /** @var AmqpMessage $message */
        $message = $context->getMessage();
        if (false == $message->isRedelivered()) {
            return;
        }

        $queueName = $context->getMessageConsumer()->getQueue()->getQueueName();

        $deadQueue = $session->createQueue($queueName.'.delayed');
        $deadQueue->setDurable(true);
        $deadQueue->setTable([
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $queueName,
            'x-message-ttl' => 5000,
            'x-expires' => 200000,
        ]);
        $session->declareQueue($deadQueue);
        $context->getLogger()->debug(sprintf(
            '[DelayDeadAmqpExtension] Declare dead queue: %s',
            $deadQueue->getQueueName()
        ));

        $properties = $message->getProperties();

        // The x-death header must be removed because of the bug in RabbitMQ.
        // It was reported that the bug is fixed since 3.5.4 but I tried with 3.6.1 and the bug still there.
        // https://github.com/rabbitmq/rabbitmq-server/issues/216
        unset($properties['x-death']);

        $deadMessage = $session->createMessage($message->getBody(), $properties, $message->getHeaders());
        
        $session->createProducer()->send($deadQueue, $deadMessage);
        $context->getLogger()->debug('[DelayDeadAmqpExtension] Send message to dead queue');

        $context->setStatus(MessageProcessor::REJECT);
        $context->getLogger()->debug('[DelayDeadAmqpExtension] Reject original message');
    }
}
