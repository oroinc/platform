<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface FrontProducerInterface
{
    /**
     * @param string $messageName
     * @param string $messageBody
     */
    public function sendMessage($messageName, $messageBody);
}
