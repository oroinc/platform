<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Region;

class LoadRegionData extends AbstractFixture
{
    const REGION_AD_07 = 'region.ad07';
    const REGION_DK_85 = 'region.dk85';
    const REGION_US_NY = 'region.usny';

    /** @var array */
    protected $regions = [
        self::REGION_AD_07 => [
            'combinedCode' => 'AD-07',
            'code' => '07',
            'name' => 'Andorra la Vella',
        ],
        self::REGION_DK_85 => [
            'combinedCode' => 'DK-85',
            'code' => '85',
            'name' => 'Sjælland',
        ],
        self::REGION_US_NY => [
            'combinedCode' => 'US-NY',
            'code' => 'US',
            'name' => 'New York',
        ],
    ];

    /**
     * Load address types
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Region::class);

        foreach ($this->regions as $reference => $data) {
            /** @var $region Region */
            $region = $repository->find($data['combinedCode']);
            if (!$region) {
                $region = new Region($data['combinedCode']);
                $region->setCode($data['code']);
                $region->setName($data['name']);
            }

            $this->setReference($reference, $region);
            $manager->persist($region);
        }

        $manager->flush();
    }
}
