<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepository extends EntityRepository
{
    /**
     * @param string $relatedEntity
     * @return WorkflowDefinition[]
     */
    public function findActiveForRelatedEntity($relatedEntity)
    {
        $criteria = [
            'relatedEntity' => $relatedEntity,
            'active' => true,
        ];

        return $this->findBy($criteria, ['priority' => 'ASC']);
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
        return $this->findBy(['active' => true], ['priority' => 'ASC']);
    }
}
