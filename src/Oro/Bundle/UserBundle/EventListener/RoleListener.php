<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Updates the security identity name for changed role.
 * Generates unique name for the newly created role without explicitly specified role name.
 */
class RoleListener
{
    private const ROLE_UPDATE_MAX_ATTEMPTS = 10;
    private const ROLE_FIELD_NAME = 'role';

    private AclSidManager $aclSidManager;

    public function __construct(AclSidManager $aclSidManager)
    {
        $this->aclSidManager = $aclSidManager;
    }

    public function preUpdate(AbstractRole $role, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField(self::ROLE_FIELD_NAME)) {
            $oldRoleName = $eventArgs->getOldValue(self::ROLE_FIELD_NAME);
            $newRoleName = $eventArgs->getNewValue(self::ROLE_FIELD_NAME);
            $this->aclSidManager->updateSid($this->aclSidManager->getSid($newRoleName), $oldRoleName);
        }
    }

    public function prePersist(AbstractRole $role, LifecycleEventArgs $eventArgs): void
    {
        if ($role->getRole()) {
            return;
        }

        $repository = $eventArgs->getObjectManager()->getRepository(Role::class);
        $attemptCount = 0;
        do {
            $attemptCount++;
            $roleName = $role->generateUniqueRole();
            if (null === $repository->findOneBy([self::ROLE_FIELD_NAME => $roleName])) {
                $role->setRole($roleName, false);
                break;
            }
        } while ($attemptCount < self::ROLE_UPDATE_MAX_ATTEMPTS);

        if (self::ROLE_UPDATE_MAX_ATTEMPTS === $attemptCount) {
            throw new \LogicException(sprintf(
                '%d attempts to generate unique role are failed.',
                self::ROLE_UPDATE_MAX_ATTEMPTS
            ));
        }
    }
}
