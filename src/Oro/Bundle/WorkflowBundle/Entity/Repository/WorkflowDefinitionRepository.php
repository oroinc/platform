<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepository extends EntityRepository
{
    const ACTIVE_WORKFLOW_DEFINITIONS_CACHE_ID = 'oro_active_workflow_definitions_cache';
    const ACTIVE_FOR_ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID = 'oro_active_for_entity_workflow_definitions_cache';
    const ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID = 'oro_entity_workflow_definitions_cache';
    const RELATED_ENTITY_CLASSES_CACHE_ID = 'oro_related_entity_classes_workflow_definitions_cache';

    /**
     * @param string $relatedEntity
     * @return WorkflowDefinition[]
     */
    public function findActiveForRelatedEntity($relatedEntity)
    {
        $qb = $this->createQueryBuilder('wd');
        return $qb->where($qb->expr()->eq('wd.relatedEntity', ':relatedEntity'))
            ->andWhere($qb->expr()->eq('wd.active', ':active'))
            ->orderBy('wd.priority', 'ASC')
            ->setParameters([
                'relatedEntity' => ClassUtils::getRealClass($relatedEntity),
                'active' => true
            ])
            ->getQuery()
            ->useResultCache(true)
            ->setResultCacheId(self::ACTIVE_FOR_ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID)
            ->getResult();
    }

    /**
     * @param string $relatedEntity
     * @return WorkflowDefinition[]
     */
    public function findForRelatedEntity($relatedEntity)
    {
        $qb = $this->createQueryBuilder('wd');
        return $qb->where($qb->expr()->eq('wd.relatedEntity', ':relatedEntity'))
            ->setParameters(['relatedEntity' => ClassUtils::getRealClass($relatedEntity)])
            ->orderBy('wd.priority', 'ASC')
            ->getQuery()
            ->useResultCache(true)
            ->setResultCacheId(self::ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID)
            ->getResult();
    }

    /**
     * @param array $names
     * @param ScopeCriteria $scopeCriteria
     * @return array|WorkflowDefinition[]
     */
    public function getScopedByNames(array $names, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->createQueryBuilder('wd');
        $qb->join('wd.scopes', 'scopes', Join::WITH)
            ->andWhere($qb->expr()->in('wd.name', ':names'))
            ->setParameter('names', $names);

        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return WorkflowDefinition[]
     */
    public function findActive()
    {
        $qb = $this->createQueryBuilder('wd');
        return $qb->where($qb->expr()->eq('wd.active', ':active'))
            ->orderBy('wd.priority', 'ASC')
            ->setParameters(['active' => true])
            ->getQuery()
            ->useResultCache(true)
            ->setResultCacheId(self::ACTIVE_WORKFLOW_DEFINITIONS_CACHE_ID)
            ->getResult();
    }

    /**
     * @param bool $activeOnly
     * @return array
     */
    public function getAllRelatedEntityClasses($activeOnly = false)
    {
        $qb = $this->createQueryBuilder('wd')
            ->resetDQLPart('select')
            ->select('DISTINCT(wd.relatedEntity) AS class_name');

        if ($activeOnly) {
            $qb->where('wd.active = :active');
            $qb->setParameter('active', true);
        }

        $data = $qb
            ->getQuery()
            ->useResultCache(true)
            ->setResultCacheId(self::RELATED_ENTITY_CLASSES_CACHE_ID)
            ->getArrayResult();

        return array_column($data, 'class_name');
    }

    /**
     * @return void
     */
    public function invalidateCache()
    {
        $cache = $this->_em->getConfiguration()->getResultCacheImpl();
        if (!$cache) {
            return;
        }

        $cache->delete(self::ACTIVE_WORKFLOW_DEFINITIONS_CACHE_ID);
        $cache->delete(self::RELATED_ENTITY_CLASSES_CACHE_ID);
        $cache->delete(self::ACTIVE_FOR_ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID);
        $cache->delete(self::ENTITY_WORKFLOW_DEFINITIONS_CACHE_ID);
    }
}
