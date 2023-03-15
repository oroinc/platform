<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Provider that returns set of roles can be used as user roles.
 */
class RolesChoicesForUserProvider implements RolesChoicesForUserProviderInterface
{
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles(): array
    {
        $qb = $this->doctrine->getRepository(Role::class)
            ->createQueryBuilder('r')
            ->orderBy('r.label');

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getChoiceLabel(Role $role): string
    {
        return $role->getLabel();
    }
}
