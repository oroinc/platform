<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

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
