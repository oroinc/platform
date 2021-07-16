<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Interface for navigation items providers.
 */
interface NavigationItemsProviderInterface
{
    public function getNavigationItems(
        UserInterface $user,
        Organization $organization,
        string $type,
        array $options = []
    ): array;
}
