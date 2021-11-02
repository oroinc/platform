<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\TranslationBundle\Download\CachingTranslationMetricsProvider;
use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Psr\Log\LoggerInterface;

/** @coversDefaultClass \Oro\Bundle\TranslationBundle\Download\CachingTranslationMetricsProvider */
class CachingTranslationMetricsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var TranslationServiceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $adapter;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CachingTranslationMetricsProvider */
    private $provider;

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
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with(CachingTranslationMetricsProvider::CACHE_KEY)
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
            $metrics[$languageCode]['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));
        }

        self::assertEquals($metrics, $this->provider->getAll());
        self::assertEquals($metrics, $this->provider->getAll());
    }

    /**
     * @covers ::populateMetrics
     * @covers ::fetchMetrics
     */
    public function testPopulateMetricsFetchesMetricsFromAdapterIfNotCachedAndCachesThem(): void
    {
        $metrics = OroTranslationServiceAdapterTest::METRICS;

        $this->cache->expects(self::exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [CachingTranslationMetricsProvider::CACHE_KEY],
                [CachingTranslationMetricsProvider::CACHE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $metrics
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with(
                CachingTranslationMetricsProvider::CACHE_KEY,
                $metrics,
                self::anything()
            );

        $this->adapter->expects(self::once())
            ->method('fetchTranslationMetrics')
            ->willReturn($metrics);

        foreach ($metrics as $languageCode => $data) {
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
        $this->adapter->expects(self::once())
            ->method('fetchTranslationMetrics')
            ->willReturn(OroTranslationServiceAdapterTest::METRICS);

        $data = OroTranslationServiceAdapterTest::METRICS['uk_UA'];
        $data['lastBuildDate'] = new \DateTime($data['lastBuildDate'], new \DateTimeZone('UTC'));

        self::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        self::assertEquals($data, $this->provider->getForLanguage('uk_UA'));
        // checking that null is returned for languages non known to the translation service
        self::assertNull($this->provider->getForLanguage('non-existent'));
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

        $expected['fr_CA']['lastBuildDate'] = new \DateTime(
            $original['fr_CA']['lastBuildDate'],
            new \DateTimeZone('UTC')
        );

        $this->adapter->expects(self::any())
            ->method('fetchTranslationMetrics')
            ->willReturn($original);

        $actual = $this->provider->getAll();

        self::assertSame($expected['uk_UA'], $actual['uk_UA']);
        self::assertSame($expected['de_DE'], $actual['de_DE']);
        self::assertSame($expected['fr_FR'], $actual['fr_FR']);
        self::assertEquals($expected['fr_CA'], $actual['fr_CA']);
    }

    /** @covers ::fetchMetrics */
    public function testFetchMetricsSilentlyLogsTranslationAdapterExceptions(): void
    {
        $this->cache->expects(self::any())
            ->method('fetch')
            ->willReturn(false);
        $adapterException = new \RuntimeException('test message');
        $this->adapter->expects(self::any())
            ->method('fetchTranslationMetrics')
            ->willThrowException($adapterException);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch translation metrics.', ['exception' => $adapterException]);

        self::assertEquals([], $this->provider->getAll());
    }
}
