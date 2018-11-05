<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads some countries and regions from the database.
 */
class LoadCountriesAndRegions extends AbstractFixture implements InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadCountries($manager);
        $this->loadRegions($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadCountries(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Country::class);
        $this->addReference('country_usa', $repository->find('US'));
        $this->addReference('country_israel', $repository->find('IL'));
        // a country without regions
        $this->addReference('country_isle_of_man', $repository->find('IM'));
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadRegions(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Region::class);
        $this->addReference('region_usa_california', $repository->find('US-CA'));
        $this->addReference('region_usa_florida', $repository->find('US-FL'));
        $this->addReference('region_israel_telaviv', $repository->find('IL-TA'));
        $this->addReference('region_israel_hefa', $repository->find('IL-HA'));
    }
}
