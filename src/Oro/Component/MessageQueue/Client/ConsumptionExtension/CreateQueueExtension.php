<?php
namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Client\SessionInterface;

class CreateQueueExtension implements ExtensionInterface
{
    use ExtensionTrait;
    
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
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
