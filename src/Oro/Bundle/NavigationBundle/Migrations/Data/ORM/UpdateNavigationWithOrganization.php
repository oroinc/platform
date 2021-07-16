<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Loads organizations for NavigationItem and NavigationHistoryItem entities.
 */
class UpdateNavigationWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    RenamedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\UserBundle\\Migrations\\Data\\ORM\\UpdateNavigationWithOrganization',
        ];
    }
    /**
     * Update navigation with organization
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroNavigationBundle:NavigationItem');
        $this->update($manager, 'OroNavigationBundle:NavigationHistoryItem');
    }
}
