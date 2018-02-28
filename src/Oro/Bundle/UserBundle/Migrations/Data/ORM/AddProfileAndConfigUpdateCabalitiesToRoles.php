<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

class AddProfileAndConfigUpdateCabalitiesToRoles extends AbstractLoadAclData
{
    /**
     * {@inheritdoc}
     */
    public function getDataPath()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
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
