<?php
namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Client\DriverInterface;

class CreateQueueExtension implements ExtensionInterface
{
    use ExtensionTrait;
    
    /**
     * @var DriverInterface
     */
    private $session;

    /**
     * @param DriverInterface $session
     */
    public function __construct(DriverInterface $session)
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
