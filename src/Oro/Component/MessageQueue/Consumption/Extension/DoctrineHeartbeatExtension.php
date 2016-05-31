<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;

class DoctrineHeartbeatExtension implements Extension
{
    use ExtensionTrait;

    /**
     * @var ManagerRegistry[]
     */
    protected $managerRegistries;

    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(array $managerRegistries)
    {
        $this->managerRegistries = $managerRegistries;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        /** @var Connection $connection */
        foreach ($this->managerRegistries as $registry) {
            foreach ($registry->getConnections() as $connection) {
                if ($connection instanceof Connection) {
                    $this->checkDBALConnection($connection);
                } else {
                    throw new \LogicException(sprintf(
                        'Got unsupported Connection instance. "%s"',
                        is_object($connection) ? get_class($connection) : gettype($connection)
                    ));
                }
            }
        }
    }

    /**
     * @param Connection $connection
     */
    protected function checkDBALConnection(Connection $connection)
    {
        if ($connection->ping()) {
            return;
        }

        $connection->close();
        $connection->connect();
    }
}
