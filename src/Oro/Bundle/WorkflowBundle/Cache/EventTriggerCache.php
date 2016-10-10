<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\Repository\EventTriggerRepositoryInterface;

class EventTriggerCache
{
    const DATA = 'data';
    const BUILT = 'built';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var CacheProvider */
    protected $provider;

    /** @var string */
    protected $triggerClassName;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param CacheProvider $provider
     */
    public function setProvider(CacheProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string $triggerClassName
     */
    public function setTriggerClassName($triggerClassName)
    {
        $this->triggerClassName = $triggerClassName;
    }

    /**
     * Write to cache entity classes and appropriate events
     *
     * @return array
     */
    public function build()
    {
        $this->assertConfigured();

        // get all triggers data
        $triggers = $this->getRepository()->getAvailableEventTriggers();
        $data = [];

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getEntityClass();
            $event = $trigger->getEvent();

            if (!isset($data[$entityClass])) {
                $data[$entityClass] = [];
            }

            if (!in_array($event, $data[$entityClass], true)) {
                $data[$entityClass][] = $event;
            }
        }

        // write trigger data to cache
        $this->provider->deleteAll();
        $this->provider->save(self::DATA, $data);
        $this->provider->save(self::BUILT, true);

        return $data;
    }

    /**
     * @param string $entityClass
     * @param string $event
     * @return bool
     * @throws \LogicException
     */
    public function hasTrigger($entityClass, $event)
    {
        $this->assertConfigured();

        if (!$this->isBuilt()) {
            $data = $this->build();
        } else {
            $data = $this->provider->fetch(self::DATA);
        }

        return !empty($data[$entityClass]) && in_array($event, $data[$entityClass], true);
    }

    /**
     * @return bool
     */
    protected function isBuilt()
    {
        return $this->provider->contains(self::BUILT) && $this->provider->fetch(self::BUILT);
    }

    /**
     * @throws \LogicException
     */
    protected function assertConfigured()
    {
        if (!$this->provider) {
            throw new \LogicException('Event trigger cache provider is not defined');
        }

        if (!$this->triggerClassName) {
            throw new \LogicException('Event trigger class name is not defined');
        }
    }

    /**
     * @return EventTriggerRepositoryInterface
     * @throws \LogicException
     */
    protected function getRepository()
    {
        $repository = $this->registry->getManagerForClass($this->triggerClassName)
            ->getRepository($this->triggerClassName);

        if (!$repository instanceof EventTriggerRepositoryInterface) {
            throw new \RuntimeException('Invalid repository');
        }

        return $repository;
    }
}
