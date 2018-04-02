<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionRepository extends EntityRepository
{
    /**
     * @param string $like
     * @param string $escapeChar
     * @return array|ProcessDefinition[]
     */
    public function findLikeName($like, $escapeChar = '!')
    {
        if (\strlen($escapeChar) > 1) {
            throw new \InvalidArgumentException(sprintf('Unexpected escape char "%s"', $escapeChar));
        }

        $qb = $this->createQueryBuilder('p');
        
        return $qb->where($qb->expr()->like('p.name', sprintf(":nameLike ESCAPE '%s'", $escapeChar)))
            ->setParameter('nameLike', $like)
            ->getQuery()
            ->getResult();
    }
}
