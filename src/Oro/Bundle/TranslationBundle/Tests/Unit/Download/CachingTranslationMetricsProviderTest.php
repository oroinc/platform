<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\TranslationBundle\Download\CachingTranslationMetricsProvider;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Psr\Log\LoggerInterface;

/** @coversDefaultClass \Oro\Bundle\TranslationBundle\Download\CachingTranslationMetricsProvider */
class CachingTranslationMetricsProviderTest extends \PHPUnit\Framework\TestCase
{
    private Cache $cache;
    private TranslationServiceAdapterInterface $adapter;
    private LoggerInterface $logger;
    private TranslationMetricsProviderInterface $provider;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->adapter = $this->createMock(TranslationServiceAdapterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new CachingTranslationMetricsProvider(
            $this->adapter,
            $this->cache,
            $this->logger
        );
    }

    /**
     * @covers ::getAll
     * @covers ::populateMetrics
     */
    public function testGetAll(): void
    {
        $metrics = OroTranslationServiceAdapterTest::METRICS;

        // once() to check that on subsequent calls to getAll() the populateMetrics() is not called again
        $this->cache->expects(static::once())
            ->method('fetch')
            ->with(CachingTranslationMetricsProvider::CACHE_KEY)
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $metrics[$languageCode]['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));
        }

        static::assertEquals($metrics, $this->provider->getAll());
        static::assertEquals($metrics, $this->provider->getAll());
    }

    /**
     * @covers ::populateMetrics
     * @covers ::fetchMetrics
     */
    public function testPopulateMetricsFetchesMetricsFromAdapterIfNotCachedAndCachesThem(): void
    {
        $metrics = OroTranslationServiceAdapterTest::METRICS;

        $this->cache->expects(static::exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [CachingTranslationMetricsProvider::CACHE_KEY],
                [CachingTranslationMetricsProvider::CACHE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $metrics
            );

        $this->cache->expects(static::once())
            ->method('save')
            ->with(
                CachingTranslationMetricsProvider::CACHE_KEY,
                $metrics,
                static::anything()
            );

        $this->adapter->expects(static::once())
            ->method('fetchTranslationMetrics')
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $metrics[$languageCode]['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));
        }
        $instance1 = new CachingTranslationMetricsProvider($this->adapter, $this->cache, $this->logger);
        $instance2 = new CachingTranslationMetricsProvider($this->adapter, $this->cache, $this->logger);

        $instance1->getAll();
        $instance2->getAll();
    }

    /** @covers ::getForLanguage */
    public function testGetForLanguage(): void
    {
        // once() to check that on subsequent calls to getForLanguage() the populateMetrics() is not called again
        $this->adapter->expects(static::once())
            ->method('fetchTranslationMetrics')
            ->willReturn(OroTranslationServiceAdapterTest::METRICS);

        $data = OroTranslationServiceAdapterTest::METRICS['uk_UA'];
        /** @noinspection PhpUnhandledExceptionInspection */
        $data['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));

        static::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        static::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        // checking that null is returned for languages non known to the translation service
        static::assertNull($this->provider->getForLanguage('non-existent'));
    }

    /** @covers ::convertLastBuildDateToDateTimeOrUnset */
    public function testConvertLastBuildDateToDateTimeOrUnset(): void
    {
        $original = OroTranslationServiceAdapterTest::METRICS;
        $expected = OroTranslationServiceAdapterTest::METRICS;

        $original['uk_UA']['lastBuildDate'] = 'not a date string';
        unset($expected['uk_UA']['lastBuildDate']);

        $date = new \DateTime();
        $original['de_DE']['lastBuildDate'] = $date; // already a \DateTimeInterface instance
        $expected['de_DE']['lastBuildDate'] = $date;

        $original['fr_FR']['lastBuildDate'] = 12345; // neither string nor date
        unset($expected['fr_FR']['lastBuildDate']);

        /** @noinspection PhpUnhandledExceptionInspection */
        $expected['fr_CA']['lastBuildDate'] = new \DateTime(
            $original['fr_CA']['lastBuildDate'],
            new \DateTimeZone('UTC')
        );

        $this->adapter->method('fetchTranslationMetrics')->willReturn($original);

        $actual = $this->provider->getAll();

        static::assertSame($expected['uk_UA'], $actual['uk_UA']);
        static::assertSame($expected['de_DE'], $actual['de_DE']);
        static::assertSame($expected['fr_FR'], $actual['fr_FR']);
        static::assertEquals($expected['fr_CA'], $actual['fr_CA']);
    }

    /** @covers ::fetchMetrics */
    public function testFetchMetricsSilentlyLogsTranslationAdapterExceptions(): void
    {
        $this->cache->method('fetch')->willReturn(false);
        $adapterException = new \RuntimeException('test message');
        $this->adapter->method('fetchTranslationMetrics')->willThrowException($adapterException);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Failed to fetch translation metrics.', ['exception' => $adapterException]);

        static::assertEquals([], $this->provider->getAll());
    }
}
