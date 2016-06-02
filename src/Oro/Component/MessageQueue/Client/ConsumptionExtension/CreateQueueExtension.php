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
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param Context $context
     */
    public function onStart(Context $context)
    {
        $this->driver->createQueue($context->getQueueName());
    }
}
