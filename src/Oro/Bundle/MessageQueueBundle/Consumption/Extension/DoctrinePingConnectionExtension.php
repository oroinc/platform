<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Doctrine ping connection extension.
 */
class DoctrinePingConnectionExtension extends AbstractExtension implements ResettableExtensionInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        // All open connections was closed after each messages was processed, no sense to ping connections.
        // See \Oro\Bundle\MessageQueueBundle\Consumption\Extension\DatabaseConnectionsClearExtension
    }
}
