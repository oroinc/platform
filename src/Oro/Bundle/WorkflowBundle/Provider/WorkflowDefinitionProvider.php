<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

class WorkflowDefinitionProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var CacheProvider */
    protected $cacheProvider;

    /** @var ArrayCache */
    protected $internalCache;

    /**
     * @param ManagerRegistry $registry
     * @param CacheProvider $cacheProvider
     */
    public function __construct(ManagerRegistry $registry, CacheProvider $cacheProvider)
    {
        $this->registry = $registry;
        $this->cacheProvider = $cacheProvider;
        $this->internalCache = new ArrayCache();
    }

    public function invalidateCache()
    {
        $this->internalCache->deleteAll();
        $this->cacheProvider->deleteAll();
    }

    /**
     * @return WorkflowDefinition[]
     */
    public function getActiveDefinitions()
    {
        $cacheId = 'active_workflow_definitions';
        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findActive();
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    public function getDefinitionsForRelatedEntity($entityClass)
    {
        $cacheId = 'workflow_definitions_for_' . $entityClass;

        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findForRelatedEntity($entityClass);
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    public function getActiveDefinitionsForRelatedEntity($entityClass)
    {
        $cacheId = 'active_workflow_definitions_for_' . $entityClass;

        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findActiveForRelatedEntity($entityClass);
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return WorkflowDefinition
     *
     * @throws WorkflowNotFoundException
     */
    public function refreshWorkflowDefinition(WorkflowDefinition $definition)
    {
        if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($definition)) {
            $definitionName = $definition->getName();

            $definition = $this->getEntityRepository()->find($definitionName);
            if (!$definition) {
                throw new WorkflowNotFoundException($definitionName);
            }
        }

        return $definition;
    }

    /**
     * @param $name
     *
     * @return null|WorkflowDefinition|object
     */
    public function find($name)
    {
        return $this->getEntityRepository()->find($name);
    }

    /**
     * @param string $id
     *
     * @return false|WorkflowDefinition[]
     */
    protected function fetchCache($id)
    {
        if ($this->cacheProvider->fetch('has_cached_values')) {
            return $this->internalCache->fetch($id);
        }

        return false;
    }

    /**
     * @param string $id
     * @param WorkflowDefinition[]|array $data
     *
     * @return bool
     */
    protected function saveCache($id, $data)
    {
        $this->cacheProvider->save('has_cached_values', true);
        return $this->internalCache->save($id, $data);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    protected function getEntityRepository()
    {
        return $this->registry->getRepository(WorkflowDefinition::class);
    }
}
