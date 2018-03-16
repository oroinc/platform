<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrinePingConnectionExtension extends AbstractExtension implements ResettableExtensionInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ManagerRegistry|null */
    private $doctrine;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->doctrine = null;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if (null === $this->doctrine) {
            $this->doctrine = $this->container->get('doctrine');
        }

        $logger = $context->getLogger();
        $connections = $this->doctrine->getConnectionNames();
        foreach ($connections as $name => $serviceId) {
            $connection = $this->doctrine->getConnection($name);
            if ($connection->ping()) {
                return;
            }

            $logger->debug(sprintf('Connection "%s" is not active, trying to reconnect.', $name));

            $connection->close();
            $connection->connect();

            $logger->debug(sprintf('Connection "%s" is active now.', $name));
        }
    }
}
