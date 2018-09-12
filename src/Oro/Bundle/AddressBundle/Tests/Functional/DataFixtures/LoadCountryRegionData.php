<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class LoadCountryRegionData extends AbstractFixture implements DependentFixtureInterface
{
    const REGION_US_FL = 'region.usfl';

    /** @var array */
    protected $data = [
        LoadRegionData::REGION_US_NY => LoadCountryData::COUNTRY_USA,
        self::REGION_US_FL => LoadCountryData::COUNTRY_USA,
    ];

    /** @var array */
    protected $regions = [
        self::REGION_US_FL => [
            'combinedCode' => 'US-FL',
            'code' => 'US',
            'name' => 'Florida',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $regionReference => $countryReference) {
            /** @var $region Region */
            $region = $this->getRegion($manager, $regionReference);

            /** @var $country Country */
            $country = $this->getReference($countryReference);
            $country->addRegion($region);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCountryData::class,
            LoadRegionData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param string $regionReference
     * @return Region
     */
    private function getRegion(ObjectManager $manager, $regionReference)
    {
        $region = null;
        if ($this->hasReference($regionReference)) {
            $region = $this->getReference($regionReference);
        }

        if (!$region) {
            $data = $this->regions[$regionReference];

            $region = $manager->getRepository(Region::class)->find($data['combinedCode']);
            if (!$region) {
                $region = new Region($data['combinedCode']);
                $region->setCode($data['code']);
                $region->setName($data['name']);
            }

            $this->setReference($regionReference, $region);
            $manager->persist($region);
        }

        return $region;
    }
}
