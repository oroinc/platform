<?php
namespace Oro\Component\Messaging\ZeroConfig\ConsumptionExtension;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\ExtensionTrait;
use Oro\Component\Messaging\ZeroConfig\Session;

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
