<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface ConsumerProducerInterface
{
    public function sendMessage($consumerName, $messageHandler, $messageName, $messageBody);
}
