<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Load user auth_staus enum options.
 */
class LoadAuthStatusOptionsData extends AbstractEnumFixture
{
    #[\Override]
    protected function getData(): array
    {
        return [
            UserManager::STATUS_ACTIVE => 'Active',
            UserManager::STATUS_RESET => 'Reset',
        ];
    }

    #[\Override]
    protected function getDefaultValue(): string
    {
        return UserManager::STATUS_ACTIVE;
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return 'auth_status';
    }
}
