<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
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
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Symfony\Component\Translation\MessageCatalogue;

class TranslatorCatalogueListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CountryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $countryRepository;

    /** @var CountryTranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $countryTranslationRepository;

    /** @var RegionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $regionRepository;

    /** @var RegionTranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $regionTranslationRepository;

    /** @var AddressTypeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $addressTypeRepository;

    /** @var AddressTypeTranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $addressTypeTranslationRepository;

    protected function setUp()
    {
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->countryTranslationRepository = $this->createMock(CountryTranslationRepository::class);
        $this->regionRepository = $this->createMock(RegionRepository::class);
        $this->regionTranslationRepository = $this->createMock(RegionTranslationRepository::class);
        $this->addressTypeRepository = $this->createMock(AddressTypeRepository::class);
        $this->addressTypeTranslationRepository = $this->createMock(AddressTypeTranslationRepository::class);
    }

    public function testOnAfterCatalogueDump(): void
    {
        $this->countryRepository->expects($this->atLeastOnce())
            ->method('getAllIdentities')
            ->willReturn(['DE']);
        $this->countryRepository->expects($this->atLeastOnce())
            ->method('updateTranslations')
            ->with(['DE' => 'Germany']);

        $this->countryTranslationRepository->expects($this->once())
            ->method('updateTranslations')
            ->with(['DE' => 'Allemagne'], 'fr');

        $this->regionRepository->expects($this->atLeastOnce())
            ->method('getAllIdentities')
            ->willReturn(['US-FL']);
        $this->regionRepository->expects($this->once())
            ->method('updateTranslations')
            ->with(['US-FL' => 'Florida']);

        $this->regionTranslationRepository->expects($this->once())
            ->method('updateTranslations')
            ->with(['US-FL' => 'Floride'], 'fr');

        $this->addressTypeRepository->expects($this->atLeastOnce())
            ->method('getAllIdentities')
            ->willReturn(['billing']);

        $this->addressTypeRepository->expects($this->atLeastOnce())
            ->method('updateTranslations')
            ->with(['billing' => 'Billing']);

        $this->addressTypeTranslationRepository->expects($this->once())
            ->method('updateTranslations')
            ->with(['billing' => 'affiche'], 'fr');

        $manager = $this->configureManager();
        $listener = new TranslatorCatalogueListener($this->configureRegistry($manager));

        $listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'en',
                    [
                        'entities' => [
                            'address_type.billing' => 'Billing',
                            'address_type.shipping' => 'Shipping',
                            'country.US' => 'United States',
                            'country.DE' => 'Germany',
                            'region.US-FL' => 'Florida',
                            'region.DE-HH' => 'Hamburg',
                        ]
                    ]
                )
            )
        );
        $listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'fr',
                    [
                        'entities' => [
                            'address_type.billing' => 'affiche',
                            'address_type.shipping' => 'navigation',
                            'country.US' => 'États Unis',
                            'country.DE' => 'Allemagne',
                            'region.US-FL' => 'Floride',
                            'region.DE-HH' => 'Hambourg',
                        ]
                    ]
                )
            )
        );
    }

    public function testOnAfterCatalogueDumpWithWrongRepository(): void
    {
        $notIdentityAwareTranslationRepositoryInterface = $this->createMock(EntityRepository::class);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [AddressType::class, $notIdentityAwareTranslationRepositoryInterface],
                ]
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp(
            '/Expected repository of type ".*IdentityAwareTranslationRepositoryInterface"/'
        );

        $listener = new TranslatorCatalogueListener($this->configureRegistry($manager));

        $listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'en',
                    [
                        'entities' => [
                            'address_type.billing' => 'Billing',
                        ]
                    ]
                )
            )
        );
    }

    public function testOnAfterCatalogueDumpWithWrongTranslationRepository(): void
    {
        $notIdentityAwareTranslationRepositoryInterface = $this->createMock(EntityRepository::class);

        $this->addressTypeRepository->expects($this->atLeastOnce())
            ->method('getAllIdentities')
            ->willReturn(['billing']);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [AddressType::class, $this->addressTypeRepository],
                    [AddressTypeTranslation::class, $notIdentityAwareTranslationRepositoryInterface],
                ]
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Expected repository of type ".*TranslationRepositoryInterface"/');

        $listener = new TranslatorCatalogueListener($this->configureRegistry($manager));

        $listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'fr',
                    [
                        'entities' => [
                            'address_type.billing' => 'affiche',
                        ]
                    ]
                )
            )
        );
    }

    /**
     * @return ObjectManager
     */
    private function configureManager(): ObjectManager
    {
        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [Country::class, $this->countryRepository],
                    [CountryTranslation::class, $this->countryTranslationRepository],
                    [Region::class, $this->regionRepository],
                    [RegionTranslation::class, $this->regionTranslationRepository],
                    [AddressType::class, $this->addressTypeRepository],
                    [AddressTypeTranslation::class, $this->addressTypeTranslationRepository],
                ]
            );

        return $manager;
    }

    /**
     * @param ObjectManager $manager
     * @return ManagerRegistry
     */
    private function configureRegistry(ObjectManager $manager): ManagerRegistry
    {
        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }
}
