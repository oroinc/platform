<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Data fixture that loads test user data with dependencies on roles and groups.
 *
 * This fixture extends the abstract user data loader and ensures that user roles and groups
 * are loaded before creating test users.
 */
class LoadUserData extends AbstractLoadUserData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData',
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadGroupData',
        ];
    }
}
