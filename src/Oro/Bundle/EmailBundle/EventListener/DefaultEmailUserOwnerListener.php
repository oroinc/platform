<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Sets the default owner to EmailUser if the owner does not exist.
 *
 * Please note: The default owner is added only if it is not in the session token, this can happen if we work in the CLI
 * without the 'current-user' and 'current-organization' options
 * (see Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter).
 */
class DefaultEmailUserOwnerListener
{
    public function __construct(private DefaultUserProvider $defaultUserProvider)
    {
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof EmailUser) {
            return;
        }

        if ($entity->getOwner() && $entity->getOrganization()) {
            return;
        }

        $owner = $this->defaultUserProvider->getDefaultUser('oro_email', 'default_email_owner');
        $entity
            ->setOwner($owner)
            ->setOrganization($owner->getOrganization());
    }
}
