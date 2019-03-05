<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Interface for navigation items providers.
 */
interface NavigationItemsProviderInterface
{
    /**
     * @param UserInterface $user
     * @param Organization $organization
     * @param string $type
     * @param array $options
     *
     * @return array
     */
    public function getNavigationItems(
        UserInterface $user,
        Organization $organization,
        string $type,
        array $options = []
    ): array;
}
