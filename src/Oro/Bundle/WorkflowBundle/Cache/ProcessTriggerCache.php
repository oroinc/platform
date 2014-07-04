<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

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
     */
    public function build()
    {
        // get all triggers data
        $triggerClass = 'OroWorkflowBundle:ProcessTrigger';
        /** @var ProcessTriggerRepository $triggerRepository */
        $triggerRepository = $this->registry->getManagerForClass($triggerClass)->getRepository($triggerClass);
        /** @var ProcessTrigger[] $triggers */
        $triggers = $triggerRepository->findAllWithDefinitions();

        $data = array();
        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getDefinition()->getRelatedEntity();
            $event = $trigger->getEvent();

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
    }

    public function hasTrigger($entityClass, $event)
    {
        if (!$this->isBuilt()) {
            $this->build();
        }
    }

    /**
     * @return bool
     */
    protected function isBuilt()
    {
        return $this->provider->contains(self::BUILT) && $this->provider->fetch(self::BUILT);
    }
}
