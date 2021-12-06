<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressTypeTranslation;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeTranslationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressTypeTranslationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): AddressTypeTranslationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(AddressTypeTranslation::class);
    }

    public function testUpdateTranslations(): void
    {
        $this->getRepository()->updateTranslations(
            [
                'billing' => 'Rechnung',
                'shipping' => 'Versand'
            ],
            'de'
        );

        $shippingTranslation = $this->getRepository()->findOneBy([
            'locale' => 'de',
            'foreignKey' => 'shipping',
            'field' => 'label'
        ]);

        $billingTranslation = $this->getRepository()->findOneBy([
            'locale' => 'de',
            'foreignKey' => 'billing',
            'field' => 'label'
        ]);

        self::assertEquals('Versand', $shippingTranslation->getContent());
        self::assertEquals('Rechnung', $billingTranslation->getContent());
    }
}
