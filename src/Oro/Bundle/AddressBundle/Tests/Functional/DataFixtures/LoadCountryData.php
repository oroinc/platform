<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;

class LoadCountryData extends AbstractFixture
{
    const TEST_COUNTRY = 'test-country';

    /**
     * @var array
     */
    protected $countries = [
        self::TEST_COUNTRY => [
            'iso2Code' => 'ZZ',
            'iso3Code' => 'ZZZ',
            'name' => 'Zname',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->countries as $key => $country) {
            $entity = new Country($country['iso2Code']);
            $entity->setIso3Code($country['iso3Code']);
            $entity->setName($country['name']);

            $this->setReference($key, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
