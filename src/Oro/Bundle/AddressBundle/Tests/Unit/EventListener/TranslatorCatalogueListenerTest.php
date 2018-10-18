<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\CountryTranslation;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\RegionTranslation;
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

    /** @var TranslatorCatalogueListener */
    private $listener;

    protected function setUp()
    {
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->countryTranslationRepository = $this->createMock(CountryTranslationRepository::class);
        $this->regionRepository = $this->createMock(RegionRepository::class);
        $this->regionTranslationRepository = $this->createMock(RegionTranslationRepository::class);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $registry */
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [Country::class, $this->countryRepository],
                    [CountryTranslation::class, $this->countryTranslationRepository],
                    [Region::class, $this->regionRepository],
                    [RegionTranslation::class, $this->regionTranslationRepository],
                ]
            );

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->listener = new TranslatorCatalogueListener($registry);
    }

    public function testOnAfterCatalogueDump()
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
            ->willReturn(['US-FL' => 'Florida']);

        $this->countryTranslationRepository->expects($this->once())
            ->method('updateTranslations')
            ->willReturn(['US-FL' => 'Floride'], 'fr');

        $this->listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'en',
                    [
                        'entities' => [
                            'country.US' => 'United States',
                            'country.DE' => 'Germany',
                            'region.US-FL' => 'Florida',
                            'region.DE-HH' => 'Hamburg',
                        ]
                    ]
                )
            )
        );
        $this->listener->onAfterCatalogueDump(
            new AfterCatalogueDump(
                new MessageCatalogue(
                    'fr',
                    [
                        'entities' => [
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
}
