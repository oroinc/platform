<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroCRM\Bundle\ZendeskBundle\Entity\User;

/**
 * NavigationItem Repository interface
 */
interface NavigationRepositoryInterface
{
    /**
     * Find all navigation items for specified user, organization and type
     *
     * @param User         $user
     * @param Organization $organization
     * @param string       $type
     * @param array        $options
     *
     * @return array
     */
    public function getNavigationItems($user, $organization, $type, $options = array());
}
