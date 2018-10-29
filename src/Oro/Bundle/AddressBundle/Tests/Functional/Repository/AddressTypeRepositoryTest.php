<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressTypeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadAddressTypeData::class]);
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

        $addressTypesIterator = $this->getRepository()->getBatchIterator();

        $addressTypeNames = [];
        foreach ($addressTypesIterator as $addressType) {
            $addressTypeNames[] = $addressType->getName();
        }

        $this->assertEquals($expectedNames, $addressTypeNames);
    }

    public function testGetAllIdentities(): void
    {
        $identities = $this->getRepository()->getAllIdentities();

        self::assertCount(5, $identities);
        self::assertContains(AddressType::TYPE_BILLING, $identities);
        self::assertContains(AddressType::TYPE_SHIPPING, $identities);
        self::assertContains(LoadAddressTypeData::TYPE_HOME, $identities);
        self::assertContains(LoadAddressTypeData::TYPE_WORK, $identities);
        self::assertContains(LoadAddressTypeData::TYPE_SECRET, $identities);
    }

    public function testUpdateTranslations(): void
    {
        $this->getRepository()->updateTranslations(
            [
                'billing' => 'Rechnung',
                'shipping' => 'Versand'
            ]
        );

        $this->getRepository()->clear();

        $shippingTranslation = $this->getRepository()->findOneBy(['name' => 'shipping']);
        $billingTranslation = $this->getRepository()->findOneBy(['name' => 'billing']);

        self::assertEquals('Versand', $shippingTranslation->getLabel());
        self::assertEquals('Rechnung', $billingTranslation->getLabel());
    }

    /**
     * @return AddressTypeRepository
     */
    protected function getRepository(): AddressTypeRepository
    {
        return static::getContainer()->get('doctrine')->getRepository(AddressType::class);
    }
}
