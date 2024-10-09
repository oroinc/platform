<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository;
use Oro\Bundle\AddressBundle\EventListener\TranslatorCatalogueListener;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueInitialize;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslatorCatalogueListenerTest extends TestCase
{
    private TranslatorCatalogueListener $listener;

    private ManagerRegistry|MockObject $managerRegistry;

    private EntityManagerInterface|MockObject $entityManager;

    private AddressTypeTranslationRepository|MockObject $addressTypeTranslationRepository;

    private CountryTranslationRepository|MockObject $countryTranslationRepository;

    private RegionTranslationRepository|MockObject $regionTranslationRepository;
    private TranslationRepository|MockObject $translationRepository;

    private MessageCatalogueInterface|MockObject $messageCatalogue;

    private const ADDRESS_TYPE_TRANSLATION_DATA = [
        'billing' => 'entities.address_type.billing',
        'shipping' => 'entities.address_type.shipping'
    ];

    private const COUNTRY_TRANSLATION_DATA = [
        'BR' => 'entities.country.BR',
        'BS' => 'entities.country.BS',
        'BT' => 'entities.country.BT',
        'BV' => 'entities.country.BV',
        'BW' => 'entities.country.BW',
    ];

    private const REGION_TRANSLATION_DATA = [
        'AD-02' => 'entities.region.AD-02',
        'AD-03' => 'entities.region.AD-03',
        'AD-04' => 'entities.region.AD-04',
        'AD-05' => 'entities.region.AD-05',
        'AD-06' => 'entities.region.AD-06',
    ];

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->addressTypeTranslationRepository = $this->createMock(AddressTypeTranslationRepository::class);
        $this->countryTranslationRepository = $this->createMock(CountryTranslationRepository::class);
        $this->regionTranslationRepository = $this->createMock(RegionTranslationRepository::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
        $this->messageCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $this->listener = new TranslatorCatalogueListener(
            $this->managerRegistry
        );
    }

    public function testOnAfterCatalogueDumpUpdateDefaultTranslations(): void
    {
        $this->getRepositoryCallsTest();
        $this->messageCatalogueCallsTest(Translator::DEFAULT_LOCALE, ['entities']);
        $this->updateDefaultTranslationsCallsTest();

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        $this->listener->onAfterCatalogueInit($event);
    }

    public function testOnAfterCatalogueDumpUpdateTranslations(): void
    {
        $this->getRepositoryCallsTest();
        $this->messageCatalogueCallsTest('de', ['entities']);
        $this->updateTranslationsCallsTest('de');

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        $this->listener->onAfterCatalogueInit($event);
    }

    public function testOnAfterCatalogueDumpUpdateUnknownDomain(): void
    {
        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('getDomains')
            ->willReturn(['unknown']);

        $this
            ->managerRegistry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this
            ->entityManager
            ->expects(self::never())
            ->method('getRepository');

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        $this->listener->onAfterCatalogueInit($event);
    }

    private function getRepositoryCallsTest(): void
    {
        $this
            ->managerRegistry
            ->expects(self::exactly(6))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this
            ->entityManager
            ->expects(self::exactly(6))
            ->method('getRepository')
            ->willReturnCallback(function (string $className) {
                $repositoryClass = $className.'Repository';
                $repositoryClassName = substr($repositoryClass, strrpos($repositoryClass, '\\') + 1);
                $fieldName = lcfirst($repositoryClassName);

                return $this->{$fieldName};
            });
    }

    private function updateDefaultTranslationsCallsTest(): void
    {
        $this
            ->addressTypeTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with(self::ADDRESS_TYPE_TRANSLATION_DATA);

        $this
            ->countryTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with(self::COUNTRY_TRANSLATION_DATA);

        $this
            ->regionTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with(self::REGION_TRANSLATION_DATA);
    }

    private function updateTranslationsCallsTest(string $locale): void
    {
        $this
            ->addressTypeTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with(self::ADDRESS_TYPE_TRANSLATION_DATA, $locale);

        $this
            ->countryTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with(self::COUNTRY_TRANSLATION_DATA, $locale);

        $this
            ->regionTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with(self::REGION_TRANSLATION_DATA, $locale);
    }

    private function messageCatalogueCallsTest(string $locale, array $domains = []): void
    {
        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('getDomains')
            ->willReturn($domains);

        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('all')
            ->willReturnCallback(function (string $domain): array {
                $data = self::ADDRESS_TYPE_TRANSLATION_DATA +
                    self::COUNTRY_TRANSLATION_DATA +
                    self::REGION_TRANSLATION_DATA;
                foreach ($data as $key => $val) {
                    $prefix = explode('.', $val)[1];
                    $data[sprintf('%s.%s', $prefix, $key)] = $val;
                    unset($data[$key]);
                }

                return $data;
            });

        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('getLocale')
            ->willReturn($locale);
    }
}
