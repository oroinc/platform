<?php
namespace Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizationData extends AbstractFixture
{
    const MAIN_ORGANIZATION = 'default';

    public function load(ObjectManager $manager)
    {
        $defaultOrganization = new Organization();

        $defaultOrganization
            ->setName(self::MAIN_ORGANIZATION)
            ->setCurrency('USD')
            ->setPrecision('000 000.00');

        $manager->persist($defaultOrganization);
        $manager->flush();
    }
}
