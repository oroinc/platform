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
        /** @var Connection $connection */
        foreach ($this->registry->getConnections() as $connection) {
            if ($connection->ping()) {
                return;
            }

            $context->getLogger()->debug(
                '[DoctrinePingConnectionExtension] Connection is not active trying to reconnect.'
            );

            $connection->close();
            $connection->connect();

            $context->getLogger()->debug(
                '[DoctrinePingConnectionExtension] Connection is active now.'
            );
        }
    }
}
