<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;

/**
 * Doctrine repository for WorkflowEntityAclIdentity entity.
 */
class WorkflowEntityAclIdentityRepository extends EntityRepository
{
    /**
     * @param string $class
     * @param int $identifier
     * @return WorkflowEntityAclIdentity[]
     */
    public function findByClassAndIdentifier($class, $identifier)
    {
        return $this->findBy(
            array(
                'entityId' => $identifier,
                'entityClass' => $class,
            )
        );
    }

    /**
     * @param string $class
     * @param int $identifier
     * @return WorkflowEntityAclIdentity[]
     */
    public function findByClassAndIdentifierAndActiveWorkflows($class, $identifier): array
    {
        $qb = $this->createQueryBuilder('ai');

        return $qb->select('ai')
            ->innerJoin('ai.workflowItem', 'wi')
            ->innerJoin('wi.definition', 'wd')
            ->where(
                $qb->expr()->eq('ai.entityId', ':entityId'),
                $qb->expr()->eq('ai.entityClass', ':entityClass'),
                $qb->expr()->eq('wd.active', ':active')
            )
            ->setParameters(
                [
                    'entityId' => $identifier,
                    'entityClass' => $class,
                    'active' => true
                ]
            )
            ->getQuery()
            ->getResult();
    }
}
