<?php
namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

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
        $manager->getRepository('OroOrganizationBundle:Organization')->updateWithOrganization(
            $tableName,
            $manager->getRepository('OroOrganizationBundle:Organization')->getFirst()->getId(),
            $relationName,
            $onlyEmpty
        );
    }
}
