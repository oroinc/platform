<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

/**
 * @deprecated Will be removed at 2.7
 * Dont use this class. Use methods of
 * \Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository
 * directly instead
 */
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
        $this->getEntityRepository()->invalidateCache();
    }

    /**
     * @return WorkflowDefinition[]
     */
    public function getActiveDefinitions()
    {
        return $this->getEntityRepository()->findActive();
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    public function getDefinitionsForRelatedEntity($entityClass)
    {
        return $this->getEntityRepository()->findForRelatedEntity($entityClass);
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    public function getActiveDefinitionsForRelatedEntity($entityClass)
    {
        return $this->getEntityRepository()->findActiveForRelatedEntity($entityClass);
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
        return $this->internalCache->fetch($id);
    }

    /**
     * @param string $id
     * @param WorkflowDefinition[]|array $data
     *
     * @return bool
     */
    protected function saveCache($id, $data)
    {
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
