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

    public function createConsumerTopic();

    public function createConsumerQueue($name);

    public function createConsumerMessage($messageName, $processorName, $messageBody);

    /**
     * @return MessageProducer
     */
    public function createConsumerMessageProducer($name = null);
}
