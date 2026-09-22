<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Acl\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a role's ACL privileges have been saved when at least one entity-level
 * or field-level permission has been actually changed; carries only the changed privileges.
 */
class AclPrivilegesSavedEvent extends Event
{
    public const string NAME = 'oro_security.acl.privileges_saved';

    public function __construct(
        private readonly SecurityIdentityInterface $securityIdentity,
        private readonly Collection $privileges,
    ) {
    }

    public function getSecurityIdentity(): SecurityIdentityInterface
    {
        return $this->securityIdentity;
    }

    /**
     * @return Collection<int|string, AclPrivilege>
     */
    public function getPrivileges(): Collection
    {
        return $this->privileges;
    }
}
