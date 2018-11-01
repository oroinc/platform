<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressTypeTranslation;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeTranslationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressTypeTranslationRepositoryTest extends WebTestCase
{
    /**
     * @var AddressTypeTranslationRepository
     */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->repository = self::getContainer()->get('doctrine')->getRepository(AddressTypeTranslation::class);
    }

    public function testUpdateTranslations(): void
    {
        $this->repository->updateTranslations(
            [
                'billing' => 'Rechnung',
                'shipping' => 'Versand'
            ],
            'de'
        );

        $shippingTranslation = $this->repository->findOneBy([
            'locale' => 'de',
            'foreignKey' => 'shipping',
            'field' => 'label'
        ]);

        $billingTranslation = $this->repository->findOneBy([
            'locale' => 'de',
            'foreignKey' => 'billing',
            'field' => 'label'
        ]);

        self::assertEquals('Versand', $shippingTranslation->getContent());
        self::assertEquals('Rechnung', $billingTranslation->getContent());
    }
}
