<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use Oro\Bundle\TranslationBundle\Download\CachingTranslationMetricsProvider;
use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachingTranslationMetricsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const METRICS = [
        'uk_UA' => [
            'code' => 'uk_UA',
            'translationStatus' => 100,
            'lastBuildDate' => '2020-08-24T00:00:00+0300'
        ],
        'de_DE' => [
            'code' => 'de_DE',
            'altCode' => 'de',
            'translationStatus' => 90,
            'lastBuildDate' => '2020-10-03T23:59:59+0100'
        ],
        'fr_FR' => [
            'code' => 'fr_FR',
            'altCode' => 'fr',
            'translationStatus' => 80,
            'lastBuildDate' => '2020-07-14T00:00:00+0200'
        ],
        'fr_CA' => [
            'code' => 'fr_CA',
            'altCode' => 'fr',
            'translationStatus' => 70,
            'lastBuildDate' => '2020-07-01T23:59:59-0400'
        ],
    ];

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var TranslationServiceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $adapter;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CachingTranslationMetricsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->adapter = $this->createMock(TranslationServiceAdapterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new CachingTranslationMetricsProvider(
            $this->adapter,
            $this->cache,
            $this->logger
        );
    }

    public function testGetAll(): void
    {
        $metrics = self::METRICS;

        // once() to check that on subsequent calls to getAll() the populateMetrics() is not called again
        $this->cache->expects(self::once())
            ->method('get')
            ->with('translation_statistic')
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
            $metrics[$languageCode]['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));
        }

        self::assertEquals($metrics, $this->provider->getAll());
        // test memory cache
        self::assertEquals($metrics, $this->provider->getAll());
    }

    public function testPopulateMetricsFetchesMetricsFromAdapterIfNotCachedAndCachesThem(): void
    {
        $metrics = self::METRICS;

        $cache2 = $this->createMock(CacheInterface::class);
        $this->cache->expects(self::once())
            ->method('get')
            ->with('translation_statistic')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $cache2->expects(self::once())
            ->method('get')
            ->with('translation_statistic')
            ->willReturn($metrics);

        $this->adapter->expects(self::once())
            ->method('fetchTranslationMetrics')
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
            $metrics[$languageCode]['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));
        }
        $instance1 = new CachingTranslationMetricsProvider($this->adapter, $this->cache, $this->logger);
        $instance2 = new CachingTranslationMetricsProvider($this->adapter, $cache2, $this->logger);

        $instance1->getAll();
        // test memory cache
        $instance2->getAll();
    }

    public function testGetForLanguage(): void
    {
        // once() to check that on subsequent calls to getForLanguage() the populateMetrics() is not called again
        $this->adapter->expects(self::once())
            ->method('fetchTranslationMetrics')
            ->willReturn(self::METRICS);
        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $data = self::METRICS['uk_UA'];
        $data['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));

        self::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        self::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        // checking that null is returned for languages non known to the translation service
        self::assertNull($this->provider->getForLanguage('non-existent'));
    }

    public function testConvertLastBuildDateToDateTimeOrUnset(): void
    {
        $original = self::METRICS;
        $expected = self::METRICS;

        $original['uk_UA']['lastBuildDate'] = 'not a date string';
        unset($expected['uk_UA']['lastBuildDate']);

        $date = new \DateTime();
        $original['de_DE']['lastBuildDate'] = $date; // already a \DateTimeInterface instance
        $expected['de_DE']['lastBuildDate'] = $date;

        $original['fr_FR']['lastBuildDate'] = 12345; // neither string nor date
        unset($expected['fr_FR']['lastBuildDate']);

        $expected['fr_CA']['lastBuildDate'] = new \DateTime(
            $original['fr_CA']['lastBuildDate'],
            new \DateTimeZone('UTC')
        );

        $this->cache->expects(self::once())
            ->method('get')
            ->with('translation_statistic')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $this->adapter->expects(self::any())
            ->method('fetchTranslationMetrics')
            ->willReturn($original);

        $actual = $this->provider->getAll();

        self::assertSame($expected['uk_UA'], $actual['uk_UA']);
        self::assertSame($expected['de_DE'], $actual['de_DE']);
        self::assertSame($expected['fr_FR'], $actual['fr_FR']);
        self::assertEquals($expected['fr_CA'], $actual['fr_CA']);
    }

    public function testFetchMetricsSilentlyLogsTranslationAdapterExceptions(): void
    {
        $this->cache->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $adapterException = new \RuntimeException('test message');
        $this->adapter->expects(self::any())
            ->method('fetchTranslationMetrics')
            ->willThrowException($adapterException);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch translation metrics.', ['exception' => $adapterException]);

        self::assertSame([], $this->provider->getAll());
    }
}
