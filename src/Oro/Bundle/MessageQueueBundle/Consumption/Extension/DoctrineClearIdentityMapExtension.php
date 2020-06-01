<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clear all entity managers after each message was processed to ensure that all managed entities was detached.
 */
class DoctrineClearIdentityMapExtension extends AbstractExtension implements ResettableExtensionInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ManagerRegistry|null */
    private $doctrine;

    /** @var array [manager name => manager service id, ...] */
    private $managers = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $managers
     */
    public function setManagers(array $managers)
    {
        $this->managers = $managers;
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
    public function onPostReceived(Context $context)
    {
        $managers = $this->getAliveManagers();

        $logger = $context->getLogger();
        $logger->info('Clear entity managers identity map.', ['entity_managers' => array_keys($managers)]);
        $this->clear($managers);
    }

    /**
     * @return ObjectManager[]
     */
    private function getAliveManagers()
    {
        $aliveManagers = [];

        $managers = $this->managers;
        if (!$managers) {
            $managers = $this->doctrine->getManagerNames();
        }

        foreach ($managers as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $aliveManagers[$name] = $this->container->get($serviceId);
            }
        }

        return $aliveManagers;
    }

    /**
     * @param ObjectManager[] $managers
     */
    private function clear(array $managers)
    {
        foreach ($managers as $manager) {
            $manager->clear();
        }
    }
}
