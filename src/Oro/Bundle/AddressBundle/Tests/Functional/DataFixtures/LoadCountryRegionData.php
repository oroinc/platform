<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class LoadCountryRegionData extends AbstractFixture implements DependentFixtureInterface
{
    const REGION_US_FL = 'region.usfl';
    const REGION_US_XX = 'region.usxx';

    /** @var array */
    protected $data = [
        LoadRegionData::REGION_US_NY => LoadCountryData::COUNTRY_USA,
        self::REGION_US_FL => LoadCountryData::COUNTRY_USA,
        self::REGION_US_XX => LoadCountryData::COUNTRY_USA,
    ];

    /** @var array */
    protected $regions = [
        self::REGION_US_FL => [
            'combinedCode' => 'US-FL',
            'code' => 'US',
            'name' => 'Florida',
        ],
        self::REGION_US_XX => [
            'combinedCode' => 'US-XXXXXXXXXXXXX',
            'code' => 'US',
            'country' => 'US',
            'name' => 'XXX',
            'deleted' => true
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $regionReference => $countryReference) {
            /** @var Region $region */
            $region = $this->getRegion($manager, $regionReference);

            /** @var Country $country */
            $country = $this->getReference($countryReference);
            $country->addRegion($region);
        }

        $manager->flush();
    }

    #[\Override]
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
                $region->setDeleted($data['deleted']);
            }

            $this->setReference($regionReference, $region);
            $manager->persist($region);
        }

        return $region;
    }
}
