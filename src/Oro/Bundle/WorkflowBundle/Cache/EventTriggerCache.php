<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\Repository\EventTriggerRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The event trigger cache.
 */
class EventTriggerCache
{
    private const DATA = 'data';
    private const BUILT = 'built';

    protected ManagerRegistry $registry;
    protected ?CacheItemPoolInterface $provider = null;
    protected ?string $triggerClassName = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setProvider(CacheItemPoolInterface $provider): void
    {
        $this->provider = $provider;
    }

    public function setTriggerClassName(?string $triggerClassName): void
    {
        $this->triggerClassName = $triggerClassName;
    }

    /**
     * Write to cache entity classes and appropriate events
     */
    public function build(): array
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
        $this->provider->clear();
        $dataItem = $this->provider->getItem(self::DATA);
        $this->provider->save($dataItem->set($data));
        $buildItem = $this->provider->getItem(self::BUILT);
        $this->provider->save($buildItem->set(true));

        return $data;
    }

    public function invalidateCache(): void
    {
        $this->provider->deleteItems([self::DATA, self::BUILT]);
    }

    public function hasTrigger(string $entityClass, string $event): bool
    {
        $this->assertConfigured();
        $dataItem = $this->provider->getItem(self::DATA);
        $buildItem = $this->provider->getItem(self::BUILT);
        $isBuild = $buildItem->isHit() && $buildItem->get() === true;
        if (!$isBuild || !$dataItem->isHit()) {
            $data = $this->build();
        } else {
            $data = $dataItem->get();
        }

        return !empty($data[$entityClass]) && in_array($event, $data[$entityClass], true);
    }

    /**
     * @throws \LogicException
     */
    protected function assertConfigured(): void
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
    protected function getRepository(): EventTriggerRepositoryInterface
    {
        $repository = $this->registry->getManagerForClass($this->triggerClassName)
            ->getRepository($this->triggerClassName);

        if (!$repository instanceof EventTriggerRepositoryInterface) {
            throw new \RuntimeException('Invalid repository');
        }

        return $repository;
    }
}
