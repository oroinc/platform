<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressTypeRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadAddressTypeData::class]);
    }

    private function getRepository(): AddressTypeRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(AddressType::class);
    }

    public function testGetBatchIterator(): void
    {
        $expectedNames = [
            AddressType::TYPE_BILLING,
            LoadAddressTypeData::TYPE_HOME,
            LoadAddressTypeData::TYPE_SECRET,
            AddressType::TYPE_SHIPPING,
            LoadAddressTypeData::TYPE_WORK,
        ];

        $iterator = $this->getRepository()->getBatchIterator();

        $addressTypeNames = [];
        foreach ($iterator as $addressType) {
            $addressTypeNames[] = $addressType->getName();
        }

        $this->assertEquals($expectedNames, $addressTypeNames);
    }
}
