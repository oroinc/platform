<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;

class WorkflowEntityAclRepository extends EntityRepository
{
    /**
     * @return WorkflowEntityAcl[]
     */
    public function getWorkflowEntityAcls()
    {
        return $this->createQueryBuilder('a')
            ->select('a, definition')
            ->join('a.definition', 'definition')
            ->getQuery()
            ->getResult();
    }
}
