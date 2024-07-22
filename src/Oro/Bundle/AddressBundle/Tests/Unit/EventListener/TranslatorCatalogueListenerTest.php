<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository;
use Oro\Bundle\AddressBundle\EventListener\TranslatorCatalogueListener;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;
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

    private MessageCatalogueInterface|MockObject $messageCatalogue;

    private const array ADDRESS_TYPE_TRANSLATION_DATA = ["billing", "shipping"];

    private const array COUNTRY_TRANSLATION_DATA = ["BR", "BS", "BT", "BV", "BW"];

    private const array REGION_TRANSLATION_DATA = ["AD-02", "AD-03", "AD-04", "AD-05", "AD-06"];

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->addressTypeTranslationRepository = $this->createMock(AddressTypeTranslationRepository::class);
        $this->countryTranslationRepository = $this->createMock(CountryTranslationRepository::class);
        $this->regionTranslationRepository = $this->createMock(RegionTranslationRepository::class);
        $this->messageCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $this->listener = new TranslatorCatalogueListener(
            $this->managerRegistry
        );
    }

    public function testOnAfterCatalogueDumpUpdateDefaultTranslations(): void
    {
        $this->getRepositoryCallsTest();
        $this->getRepositoryAllIdentitiesCallsTest();
        $this->messageCatalogueCallsTest(Translator::DEFAULT_LOCALE);
        $this->updateDefaultTranslationsCallsTest();

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        $this->listener->onAfterCatalogueInit($event);
    }

    public function testOnAfterCatalogueDumpUpdateTranslations(): void
    {
        $this->getRepositoryCallsTest();
        $this->getRepositoryAllIdentitiesCallsTest();
        $this->messageCatalogueCallsTest('de');
        $this->updateTranslationsCallsTest('de');

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        $this->listener->onAfterCatalogueInit($event);
    }

    public function testOnAfterCatalogueDumpUpdateTranslationException(): void
    {
        $repo = new \stdClass();

        $this
            ->managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this
            ->entityManager
            ->expects(self::any())
            ->method('getRepository')
            ->willReturn($repo);

        $event = new AfterCatalogueInitialize($this->messageCatalogue);
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(
            'Expected repository of type "%s", "%s" given',
            TranslationRepositoryInterface::class,
            get_class($repo)
        ));
        $this->listener->onAfterCatalogueInit($event);
    }

    private function getRepositoryCallsTest(): void
    {
        $this
            ->managerRegistry
            ->expects(self::exactly(3))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this
            ->entityManager
            ->expects(self::exactly(3))
            ->method('getRepository')
            ->willReturnCallback(function (string $className) {
                $repositoryClass = $className.'Repository';
                $repositoryClassName = substr($repositoryClass, strrpos($repositoryClass, '\\') + 1);
                $fieldName = lcfirst($repositoryClassName);

                return $this->{$fieldName};
            });
    }

    private function getRepositoryAllIdentitiesCallsTest(): void
    {
        $this
            ->addressTypeTranslationRepository
            ->expects(self::once())
            ->method('getAllIdentities')
            ->willReturn(self::ADDRESS_TYPE_TRANSLATION_DATA);

        $this
            ->countryTranslationRepository
            ->expects(self::once())
            ->method('getAllIdentities')
            ->willReturn(self::COUNTRY_TRANSLATION_DATA);

        $this
            ->regionTranslationRepository
            ->expects(self::once())
            ->method('getAllIdentities')
            ->willReturn(self::REGION_TRANSLATION_DATA);
    }

    private function updateDefaultTranslationsCallsTest(): void
    {
        $this
            ->addressTypeTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with([
                'billing' => 'entities.address_type.billing',
                'shipping' => 'entities.address_type.shipping'
            ]);

        $this
            ->countryTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with([
                'BR' => 'entities.country.BR',
                'BS' => 'entities.country.BS',
                'BT' => 'entities.country.BT',
                'BV' => 'entities.country.BV',
                'BW' => 'entities.country.BW',
            ]);

        $this
            ->regionTranslationRepository
            ->expects(self::once())
            ->method('updateDefaultTranslations')
            ->with([
                'AD-02' => 'entities.region.AD-02',
                'AD-03' => 'entities.region.AD-03',
                'AD-04' => 'entities.region.AD-04',
                'AD-05' => 'entities.region.AD-05',
                'AD-06' => 'entities.region.AD-06',
            ]);
    }

    private function updateTranslationsCallsTest(string $locale): void
    {
        $this
            ->addressTypeTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with([
                'billing' => 'entities.address_type.billing',
                'shipping' => 'entities.address_type.shipping'
            ], $locale);

        $this
            ->countryTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with([
                'BR' => 'entities.country.BR',
                'BS' => 'entities.country.BS',
                'BT' => 'entities.country.BT',
                'BV' => 'entities.country.BV',
                'BW' => 'entities.country.BW',
            ], $locale);

        $this
            ->regionTranslationRepository
            ->expects(self::once())
            ->method('updateTranslations')
            ->with([
                'AD-02' => 'entities.region.AD-02',
                'AD-03' => 'entities.region.AD-03',
                'AD-04' => 'entities.region.AD-04',
                'AD-05' => 'entities.region.AD-05',
                'AD-06' => 'entities.region.AD-06',
            ], $locale);
    }

    private function messageCatalogueCallsTest(string $locale): void
    {
        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('get')
            ->willReturnCallback(function (string $id, string $domain): string {
                return sprintf('%s.%s', $domain, $id);
            });

        $this
            ->messageCatalogue
            ->expects(self::any())
            ->method('getLocale')
            ->willReturn($locale);
    }
}
