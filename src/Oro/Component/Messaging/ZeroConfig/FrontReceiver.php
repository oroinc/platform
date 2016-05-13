<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session;

class FrontReceiver implements MessageProcessor, FrontReceiverInterface
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Session $session)
    {
        $messageName = $message->getProperty('messageName');
        if (false == $messageName) {
            throw new \LogicException('Got message without name');
        }

        $this->router->route($messageName, $message->getBody());
    }
}
