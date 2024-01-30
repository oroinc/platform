<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * The base class for fixtures that set a default organization to an entity.
 */
abstract class UpdateWithOrganization extends AbstractFixture
{
    /**
     * Sets a default organization to the given entity.
     */
    public function update(
        ObjectManager $manager,
        string $entityClass,
        string $relationName = 'organization',
        bool $onlyEmpty = false
    ): void {
        $organizationRepository = $manager->getRepository(Organization::class);
        $organizationRepository->updateWithOrganization(
            $entityClass,
            $organizationRepository->getFirst()->getId(),
            $relationName,
            $onlyEmpty
        );
    }
}
