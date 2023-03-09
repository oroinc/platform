<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Translation\Strategy;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LocalizationFallbackStrategyTest extends OrmTestCase
{
    private const CACHE_KEY = 'localization_fallbacks';

    /** @var EntityManagerInterface */
    private $em;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var LocalizationFallbackStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->cache = $this->createMock(CacheInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Localization::class)
            ->willReturn($this->em);

        $this->strategy = new LocalizationFallbackStrategy($doctrine, $this->cache);
    }

    public function testIsApplicable()
    {
        self::assertTrue($this->strategy->isApplicable());
    }

    /**
     * @dataProvider getLocaleFallbacksDataProvider
     */
    public function testGetLocaleFallbacks(array $rows, array $localizations)
    {
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.parent_id AS sclr_1, o1_.code AS code_2'
            . ' FROM oro_localization o0_'
            . ' INNER JOIN oro_language o1_ ON o0_.language_id = o1_.id',
            $rows
        );

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    public function getLocaleFallbacksDataProvider(): array
    {
        return [
            [
                'rows'          => [
                    ['id_0' => 1, 'sclr_1' => null, 'code_2' => 'en'],
                    ['id_0' => 2, 'sclr_1' => 1, 'code_2' => 'en_EN'],
                    ['id_0' => 3, 'sclr_1' => 2, 'code_2' => 'en_FR'],
                    ['id_0' => 4, 'sclr_1' => null, 'code_2' => 'ru'],
                    ['id_0' => 5, 'sclr_1' => 4, 'code_2' => 'ru_RU'],
                ],
                'localizations' => [
                    'en' => [
                        'en' => ['en_EN' => ['en_FR' => []]],
                        'ru' => ['ru_RU' => []],
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getLocaleFallbacksCacheDataProvider
     */
    public function testGetLocaleFallbacksCache(array $localizations)
    {
        $this->getDriverConnectionMock($this->em)->expects(self::never())
            ->method('query');

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn($localizations);

        self::assertEquals($localizations, $this->strategy->getLocaleFallbacks());
    }

    public function getLocaleFallbacksCacheDataProvider(): array
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
        self::assertEquals('oro_localization_fallback_strategy', $this->strategy->getName());
    }

    public function testClearCache()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::CACHE_KEY);

        $this->strategy->clearCache();
    }

    public function testWarmup(): void
    {
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.parent_id AS sclr_1, o1_.code AS code_2'
            . ' FROM oro_localization o0_'
            . ' INNER JOIN oro_language o1_ ON o0_.language_id = o1_.id',
            [
                ['id_0' => 1, 'sclr_1' => null, 'code_2' => 'en']
            ]
        );

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->strategy->warmUp('sample/path');
    }

    public function testWarmupWhenInvalidFieldNameException(): void
    {
        $this->getDriverConnectionMock($this->em)->expects(self::once())
            ->method('query')
            ->willThrowException($this->createMock(InvalidFieldNameException::class));

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->strategy->warmUp('sample/path');
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->strategy->isOptional());
    }
}
