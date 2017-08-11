<?php
namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrinePingConnectionExtension extends AbstractExtension
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        /** @var Connection[] $connections */
        $connections = $this->registry->getConnections();
        foreach ($connections as $name => $connection) {
            if ($connection->ping()) {
                return;
            }

            $context->getLogger()->debug(sprintf('Connection "%s" is not active, trying to reconnect.', $name));

            $connection->close();
            $connection->connect();

            $context->getLogger()->debug(sprintf('Connection "%s" is active now.', $name));
        }
    }
}
