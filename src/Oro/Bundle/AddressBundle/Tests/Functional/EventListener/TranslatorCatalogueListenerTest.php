<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\AddressTypeTranslation;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\CountryTranslation;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\RegionTranslation;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository;
use Oro\Bundle\AddressBundle\EventListener\TranslatorCatalogueListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueInitialize;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @dbIsolationPerTest
 */
class TranslatorCatalogueListenerTest extends WebTestCase
{
    private TranslatorCatalogueListener $listener;

    private ManagerRegistry $managerRegistry;

    private AddressTypeRepository $addressTypeRepository;

    private CountryRepository $countryRepository;

    private RegionRepository $regionRepository;

    private AddressTypeTranslationRepository $addressTypeTranslationRepository;

    private CountryTranslationRepository $countryTranslationRepository;

    private RegionTranslationRepository $regionTranslationRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->listener = self::getContainer()->get('oro_address.listener.translator_catalogue');
        $this->managerRegistry = self::getContainer()->get('doctrine');
        $this->addressTypeRepository =
            $this->managerRegistry->getManagerForClass(AddressType::class)->getRepository(AddressType::class);
        $this->countryRepository =
            $this->managerRegistry->getManagerForClass(Country::class)->getRepository(Country::class);
        $this->regionRepository =
            $this->managerRegistry->getManagerForClass(Region::class)->getRepository(Region::class);
        $this->addressTypeTranslationRepository = $this
            ->managerRegistry
            ->getManagerForClass(AddressTypeTranslation::class)
            ->getRepository(AddressTypeTranslation::class);
        $this->countryTranslationRepository = $this
            ->managerRegistry
            ->getManagerForClass(CountryTranslation::class)
            ->getRepository(CountryTranslation::class);
        $this->regionTranslationRepository = $this
            ->managerRegistry
            ->getManagerForClass(RegionTranslation::class)
            ->getRepository(RegionTranslation::class);
    }

    public function testOnAfterCatalogueDumpUpdateDefaultTranslations(): void
    {
        $this->listener->onAfterCatalogueInit(new AfterCatalogueInitialize(
            new MessageCatalogue(Translator::DEFAULT_LOCALE, [
                'entities' => [
                        'address_type.billing' => 'New Billing',
                        'country.AD' => 'New Andorra',
                        'region.BF-08' => 'New Region BF-08'
                    ],
                ])
        ));

        /** @var AddressType $addressType */
        $addressType = $this->addressTypeRepository->findOneBy(['name' => 'billing']);
        self::assertEquals('New Billing', $addressType->getLabel());
        /** @var Country $country */
        $country = $this->countryRepository->findOneBy(['iso2Code' => 'AD']);
        self::assertEquals('New Andorra', $country->getName());
        /** @var Region $region */
        $region = $this->regionRepository->findOneBy(['combinedCode' => 'BF-08']);
        self::assertEquals('New Region BF-08', $region->getName());
    }

    public function testOnAfterCatalogueDumpUpdateTranslations(): void
    {
        $this->listener->onAfterCatalogueInit(new AfterCatalogueInitialize(
            new MessageCatalogue('de', [
                'entities' => [
                    'address_type.billing' => 'Nieuwe facturering',
                    'country.AD' => 'Nieuw Andorra',
                    'region.BF-08' => 'Nieuwe regio BF-08'
                ],
            ])
        ));

        /** @var AddressTypeTranslation $addressTypeTranslation */
        $addressTypeTranslation = $this->addressTypeTranslationRepository->findOneBy(['foreignKey' => 'billing']);
        self::assertEquals('de', $addressTypeTranslation->getLocale());
        self::assertEquals('Nieuwe facturering', $addressTypeTranslation->getContent());
        /** @var CountryTranslation $countryTranslation */
        $countryTranslation = $this->countryTranslationRepository->findOneBy(['foreignKey' => 'AD']);
        self::assertEquals('de', $countryTranslation->getLocale());
        self::assertEquals('Nieuw Andorra', $countryTranslation->getContent());
        /** @var RegionTranslation $regionTranslation */
        $regionTranslation = $this->regionTranslationRepository->findOneBy(['foreignKey' => 'BF-08']);
        self::assertEquals('de', $regionTranslation->getLocale());
        self::assertEquals('Nieuwe regio BF-08', $regionTranslation->getContent());
    }
}
