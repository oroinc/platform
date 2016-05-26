<?php
namespace Oro\Component\MessageQueue\ZeroConfig\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\ZeroConfig\Session;

class CreateQueueExtension implements Extension
{
    use ExtensionTrait;
    
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param Context $context
     */
    public function onStart(Context $context)
    {
        $queueName = $context->getMessageConsumer()->getQueue()->getQueueName();
        
        $this->session->createQueue($queueName);
    }
}
