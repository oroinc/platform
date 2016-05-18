<?php
namespace Oro\Component\Messaging\Consumption\Extension;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\ExtensionTrait;
use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;

class PostponeDeadMessageAmqpExtension implements Extension
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
        $deadQueue = $session->createQueue('dead.'.$message->getExchange());
        $deadQueue->setDurable(true);
        $deadQueue->setTable([
            'x-dead-letter-exchange' => $message->getExchange(),
            'x-dead-letter-routing-key' => '',
            'x-message-ttl' => 20000,
            'x-expires' => 200000,
        ]);
        $session->declareQueue($deadQueue);
        $context->getLogger()->debug(sprintf(
            '[PostponeDeadMessage] Declare dead queue: %s',
            $deadQueue->getQueueName()
        ));

        $deadExchange = $session->createTopic('amq.direct');
        $deadExchange->setDurable(true);
        $deadExchange->setRoutingKey($deadQueue->getQueueName());
        $session->declareBind($deadExchange, $deadQueue);
        $context->getLogger()->debug('[PostponeDeadMessage] Declare bind dead queue to amq.direct exchange');

        $properties = $message->getProperties();
        unset($properties['x-death']);
        
        $headers = $message->getHeaders();
        unset($headers['x-death']);

        $deadMessage = $session->createMessage($message->getBody(), $properties, $headers);
        
        $session->createProducer()->send($deadExchange, $deadMessage);
        $context->getLogger()->debug('[PostponeDeadMessage] Send message to dead topic');

        $context->setStatus(MessageProcessor::REJECT);
        $context->getLogger()->debug('[PostponeDeadMessage] Reject original message');
    }
}
