<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Updates given table with default organization
 */
abstract class UpdateWithOrganization extends AbstractFixture
{
    /**
     * Update given table with default organization
     *
     * @param ObjectManager $manager
     * @param string        $tableName
     * @param string        $relationName relation name to update. By default 'organization'
     * @param bool          $onlyEmpty    Update data only for the records with empty relation
     */
    public function update(ObjectManager $manager, $tableName, $relationName = 'organization', $onlyEmpty = false)
    {
        $manager->getRepository(Organization::class)->updateWithOrganization(
            $tableName,
            $manager->getRepository(Organization::class)->getFirst()->getId(),
            $relationName,
            $onlyEmpty
        );
    }
}
