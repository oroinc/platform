<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;

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
}
