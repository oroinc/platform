<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * NavigationItem Repository interface
 */
interface NavigationRepositoryInterface
{
    /**
     * Find all navigation items for specified user, organization and type
     *
     * @param User | integer $user
     * @param Organization   $organization
     * @param string         $type
     * @param array          $options If passed $options['orderBy'], must be an array with following structure:
     *                                array(
     *                                array(
     *                                'field'   => $field_name,
     *                                'dir'  => 'ASC'|'DESC'
     *                                )
     *                                )
     *
     * @return array
     */
    public function getNavigationItems($user, Organization $organization, $type = null, $options = array());
}
