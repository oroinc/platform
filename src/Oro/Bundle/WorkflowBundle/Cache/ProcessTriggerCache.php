<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerCache
{
    const DATA  = 'data';
    const BUILT = 'built';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CacheProvider
     */
    protected $provider;

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
     * Write to cache entity classes and appropriate events
     *
     * @return array
     */
    public function build()
    {
        $this->assertProvider();

        // get all triggers data
        $triggerRepository = $this->registry
            ->getManagerForClass('OroWorkflowBundle:ProcessTrigger')
            ->getRepository('OroWorkflowBundle:ProcessTrigger');
        /** @var ProcessTrigger[] $triggers */
        $triggers = $triggerRepository->findAllWithDefinitions();
        $data     = array();

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getDefinition()->getRelatedEntity();
            $event       = $trigger->getEvent();

            if (!isset($data[$entityClass])) {
                $data[$entityClass] = array();
            }

            if (!in_array($event, $data[$entityClass])) {
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
     */
    public function hasTrigger($entityClass, $event)
    {
        $this->assertProvider();

        if (!$this->isBuilt()) {
            $data = $this->build();
        } else {
            $data = $this->provider->fetch(self::DATA);
        }

        return !empty($data[$entityClass]) && in_array($event, $data[$entityClass]);
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
    protected function assertProvider()
    {
        if (!$this->provider) {
            throw new \LogicException('Process trigger cache provider is not defined');
        }
    }
}
