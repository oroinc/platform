<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\WorkflowBundle\Entity\Repository\EventTriggerRepositoryInterface;

class EventTriggerCache
{
    const DATA = 'data';
    const BUILT = 'built';

    /** @var CacheProvider */
    protected $provider;

    /** @var EventTriggerRepositoryInterface */
    protected $repository;

    /**
     * @param CacheProvider $provider
     */
    public function setProvider(CacheProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param EventTriggerRepositoryInterface $triggerRepository
     */
    public function setEventTriggerRepository(EventTriggerRepositoryInterface $triggerRepository)
    {
        $this->repository = $triggerRepository;
    }

    /**
     * Write to cache entity classes and appropriate events
     *
     * @return array
     * @throws \LogicException
     */
    public function build()
    {
        $this->assertConfigured();

        // get all triggers data
        $triggers = $this->repository->getAvailableEventTriggers();
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

        if (!$this->repository) {
            throw new \LogicException('Event trigger repository is not defined');
        }
    }
}
