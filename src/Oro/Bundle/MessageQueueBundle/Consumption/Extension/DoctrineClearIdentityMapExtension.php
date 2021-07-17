<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\Persistence\ObjectManager;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clear all entity managers after each message was processed to ensure that all managed entities was detached.
 */
class DoctrineClearIdentityMapExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /** @var array [manager name => manager service id, ...] */
    private $managers;

    public function __construct(ContainerInterface $container, array $managers)
    {
        $this->container = $container;
        $this->managers = $managers;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $managers = $this->getAliveManagers();

        $logger = $context->getLogger();
        $logger->info('Clear entity managers identity map.', ['entity_managers' => array_keys($managers)]);
        $this->clear($managers);
    }

    /**
     * @return ObjectManager[]
     */
    private function getAliveManagers(): array
    {
        $aliveManagers = [];

        foreach ($this->managers as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $aliveManagers[$name] = $this->container->get($serviceId);
            }
        }

        return $aliveManagers;
    }

    /**
     * @param ObjectManager[] $managers
     */
    private function clear(array $managers): void
    {
        foreach ($managers as $manager) {
            $manager->clear();
        }
    }
}
