<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AddressTypeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypeData']);
    }

    public function testGetBatchIterator()
    {
        $expectedNames = [
            AddressType::TYPE_BILLING,
            LoadAddressTypeData::TYPE_HOME,
            LoadAddressTypeData::TYPE_SECRET,
            AddressType::TYPE_SHIPPING,
            LoadAddressTypeData::TYPE_WORK,
        ];

        $addressTypesIterator = $this->getRepository()->getBatchIterator();

        $addressTypeNames = [];
        foreach ($addressTypesIterator as $addressType) {
            $addressTypeNames[] = $addressType->getName();
        }

        $this->assertEquals($expectedNames, $addressTypeNames);
    }

    /**
     * @return AddressTypeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'Oro\Bundle\AddressBundle\Entity\AddressType'
        );
    }
}
