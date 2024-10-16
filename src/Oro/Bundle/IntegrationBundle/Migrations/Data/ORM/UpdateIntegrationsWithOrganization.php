<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

/**
 * Sets a default organization to Channel entity.
 */
class UpdateIntegrationsWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAdminUserData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Channel::class);
    }
}
