<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getByNamesQueryBuilder(array $names)
    {
        $qb = $this->createQueryBuilder('wd', 'wd.name');

        $qb->andWhere($qb->expr()->in('wd.name', ':names'));
        $qb->setParameter('names', $names);

        return $qb;
    }
}
