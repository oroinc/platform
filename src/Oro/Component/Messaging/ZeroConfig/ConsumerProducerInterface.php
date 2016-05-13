<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface ConsumerProducerInterface
{
    /**
     * @param string $consumerName
     * @param string $messageHandler
     * @param string $messageName
     * @param string $messageBody
     */
    public function sendMessage($consumerName, $messageHandler, $messageName, $messageBody);
}
