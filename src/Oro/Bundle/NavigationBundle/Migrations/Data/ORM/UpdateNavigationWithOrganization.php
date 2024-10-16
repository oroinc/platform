<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

/**
 * Sets a default organization to NavigationItem and NavigationHistoryItem entities.
 */
class UpdateNavigationWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    RenamedFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAdminUserData::class];
    }

    #[\Override]
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\UserBundle\\Migrations\\Data\\ORM\\UpdateNavigationWithOrganization',
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, NavigationItem::class);
        $this->update($manager, NavigationHistoryItem::class);
    }
}
