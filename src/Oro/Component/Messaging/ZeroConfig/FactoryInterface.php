<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Queue;
use Oro\Component\Messaging\Transport\Topic;

interface FactoryInterface
{
    /**
     * @param string $messageName
     * @param string $messageBody
     *
     * @return Message
     */
    public function createRouterMessage($messageName, $messageBody);

    /**
     * @return Topic
     */
    public function createRouterTopic();

    /**
     * @return Queue
     */
    public function createRouterQueue();

    /**
     * @return MessageProducer
     */
    public function createRouterMessageProducer();

    /**
     * @param string $messageName
     * @param string $handlerName
     * @param string $messageBody
     *
     * @return Message
     */
    public function createConsumerMessage($messageName, $handlerName, $messageBody);

    /**
     * @param string $consumerName
     *
     * @return Topic
     */
    public function createConsumerTopic($consumerName);

    /**
     * @param string $consumerName
     *
     * @return Queue
     */
    public function createConsumerQueue($consumerName);

    /**
     * @param string $consumerName
     *
     * @return MessageProducer
     */
    public function createConsumerMessageProducer($consumerName);
}
