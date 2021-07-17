<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationFallbackStrategyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var LocalizationFallbackStrategy
     */
    protected $strategy;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'save', 'delete'])->getMockForAbstractClass();
        $this->strategy = new LocalizationFallbackStrategy($this->doctrine, $this->cache);
        $this->strategy->setEntityClass(Localization::class);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->strategy->isApplicable());
    }

    /**
     * @dataProvider getLocaleFallbacksDataProvider
     *
     * @param array|null $entities
     * @param array $localizations
     */
    public function testGetLocaleFallbacks($entities, array $localizations)
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(LocalizationFallbackStrategy::CACHE_KEY)
            ->willReturn(false);
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\LocaleBundle\Entity\Localization')
            ->willReturn($em);
        /** @var LocalizationRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(LocalizationRepository::class);
        $em->expects($this->once())->method('getRepository')->with(Localization::class)->willReturn($repository);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(LocalizationFallbackStrategy::CACHE_KEY, $localizations)
            ->willReturn((bool)$entities);
        $this->assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksDataProvider()
    {
        $secondLevelLevelEn = $this->getEntity(
            Localization::class,
            [
                'name' => 'English1',
                'language' => $this->getEntity(Language::class, ['code' => 'en']),
                'formattingCode' => 'en_FR',
            ]
        );
        $firstLevelEn = $this->getEntity(
            Localization::class,
            [
                'name' => 'English2',
                'language' => $this->getEntity(Language::class, ['code' => 'en']),
                'formattingCode' => 'en_EN',
                'childLocalizations' => new ArrayCollection([$secondLevelLevelEn])
            ]
        );
        $en = $this->getEntity(
            Localization::class,
            [
                'name' => 'English3',
                'language' => $this->getEntity(Language::class, ['code' => 'en']),
                'formattingCode' => 'en',
                'childLocalizations' => new ArrayCollection([$firstLevelEn])
            ]
        );
        $firstLevelRu = $this->getEntity(
            Localization::class,
            [
                'name' => 'Russian1',
                'language' => $this->getEntity(Language::class, ['code' => 'ru']),
                'formattingCode' => 'ru_RU',
            ]
        );
        $ru = $this->getEntity(
            Localization::class,
            [
                'name' => 'Russian2',
                'language' => $this->getEntity(Language::class, ['code' => 'ru']),
                'formattingCode' => 'ru',
                'childLocalizations' => new ArrayCollection([$firstLevelRu])
            ]
        );
        $localizations = [
            Configuration::DEFAULT_LOCALE => [
                'en' => ['en' => ['en' => []]],
                'ru' => ['ru' => []],
            ]
        ];
        return [
            ['entities' => [$en, $ru], 'localizations' => $localizations],
        ];
    }

    /**
     * @dataProvider getLocaleFallbacksCacheDataProvider
     */
    public function testGetLocaleFallbacksCache(array $localizations)
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(LocalizationFallbackStrategy::CACHE_KEY)
            ->willReturn($localizations);
        $this->assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksCacheDataProvider()
    {
        return [
            [
                'localizations' => [
                    'en' => ['en_EN' => ['en_FR' => []]],
                    'ru' => ['ru_RU' => []],
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationFallbackStrategy::NAME, $this->strategy->getName());
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(LocalizationFallbackStrategy::CACHE_KEY);
        $this->strategy->clearCache();
    }

    /**
     * @dataProvider getLocaleFallbacksDataProvider
     *
     * @param array|null $entities
     * @param array $localizations
     */
    public function testWarmup($entities, array $localizations): void
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Localization::class)
            ->willReturn($em = $this->createMock(EntityManager::class));

        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(Localization::class)
            ->willReturn($repository = $this->createMock(LocalizationRepository::class));

        $repository
            ->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(LocalizationFallbackStrategy::CACHE_KEY, $localizations);

        $this->strategy->warmUp('sample/path');
    }

    public function testWarmupWhenInvalidFieldNameException(): void
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Localization::class)
            ->willReturn($em = $this->createMock(EntityManager::class));

        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(Localization::class)
            ->willReturn($repository = $this->createMock(LocalizationRepository::class));

        $repository
            ->expects($this->once())
            ->method('findRootsWithChildren')
            ->willThrowException($this->createMock(InvalidFieldNameException::class));

        $this->cache
            ->expects($this->never())
            ->method('save');

        $this->strategy->warmUp('sample/path');
    }

    public function testIsOptional(): void
    {
        $this->assertTrue($this->strategy->isOptional());
    }
}
