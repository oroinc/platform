<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads the other organizations.
 */
class LoadOtherOrganizations extends AbstractFixture implements InitialFixtureInterface
{
    const ORGANIZATION_1 = 'organization_1';
    const ORGANIZATION_2 = 'organization_2';
    const ORGANIZATION_3 = 'organization_3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organizations = [
            [
                'name' => self::ORGANIZATION_1,
                'enabled' => true
            ],
            [
                'name' => self::ORGANIZATION_2,
                'enabled' => true
            ],
            [
                'name' => self::ORGANIZATION_3,
                'enabled' => true
            ]
        ];

        foreach ($organizations as $organizationData) {
            $organization = new Organization();
            $organization->setName($organizationData['name']);
            $organization->setEnabled($organizationData['enabled']);
            $manager->persist($organization);

            $manager->flush();
            $this->setReference($organizationData['name'], $organization);
        }
    }
}
