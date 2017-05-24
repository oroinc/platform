<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionProvider
{
    const CACHE_TTL = 0;

    /** @var Registry */
    protected $doctrine;

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * @param Registry $doctrine
     * @param CacheProvider $cacheProvider
     */
    public function __construct(Registry $doctrine, CacheProvider $cacheProvider)
    {
        $this->doctrine = $doctrine;
        $this->cacheProvider = $cacheProvider;
    }

    public function invalidateCache()
    {
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
     * @param string $id
     *
     * @return false|WorkflowDefinition[]
     */
    private function fetchCache($id)
    {
        if ($this->cacheProvider) {
            return $this->cacheProvider->fetch($id);
        }

        return false;
    }

    /**
     * @param string $id
     * @param WorkflowDefinition[]|array $data
     *
     * @return bool
     */
    private function saveCache($id, $data)
    {
        if ($this->cacheProvider) {
            return $this->cacheProvider->save($id, $data, self::CACHE_TTL);
        }

        return false;
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    private function getEntityRepository()
    {
        return $this->doctrine->getRepository(WorkflowDefinition::class);
    }
}
