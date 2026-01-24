<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Loads ACL data to grant profile and configuration update capabilities to all roles.
 *
 * This migration data loader grants the `update_own_profile` and `update_own_configuration`
 * action permissions to all roles, allowing users to modify their own profile information
 * and system configuration settings.
 */
class AddProfileAndConfigUpdateCabalitiesToRoles extends AbstractLoadAclData
{
    #[\Override]
    public function getDataPath()
    {
        return '';
    }

    #[\Override]
    protected function getAclData()
    {
        return [
            self::ALL_ROLES => [
                'permissions' => [
                    'action|update_own_profile'       => ['EXECUTE'],
                    'action|update_own_configuration' => ['EXECUTE'],
                ],
            ],
        ];
    }
}
