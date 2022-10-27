<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

use Oro\Bundle\TranslationBundle\Exception\TranslationServiceInvalidResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides translation metrics (translation completeness and last build date) using an adapter
 * to the translation service and caches them for 1 day.
 */
class CachingTranslationMetricsProvider implements TranslationMetricsProviderInterface
{
    private const CACHE_KEY = 'translation_statistic';
    private const CACHE_TTL = 86400;
    private const LAST_BUILD_DATE = 'lastBuildDate';

    private CacheInterface $cache;
    private TranslationServiceAdapterInterface $adapter;
    private LoggerInterface $logger;

    /** @var array|null [language code => [key => value, ...], ... ] */
    private ?array $metrics = null;

    public function __construct(
        TranslationServiceAdapterInterface $adapter,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getAll(): array
    {
        if (null === $this->metrics) {
            $this->metrics = $this->loadMetrics();
        }

        return $this->metrics;
    }

    public function getForLanguage(string $languageCode): ?array
    {
        $metrics = $this->getAll();

        return $metrics[$languageCode] ?? null;
    }

    /**
     * Retrieves metrics from the cache if already cached, or from the translation service adapter
     * (and populates the cache) otherwise.
     */
    private function loadMetrics(): array
    {
        $data = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                return $this->adapter->fetchTranslationMetrics();
            } catch (TranslationServiceInvalidResponseException $e) {
                $this->logger->error(
                    'Failed to fetch translation metrics.',
                    ['exception' => $e, 'response_body_contents' => $e->getResponse()]
                );
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch translation metrics.', ['exception' => $e]);
            }

            return null;
        });

        $allMetrics = [];
        if ($data) {
            foreach ($data as $metrics) {
                $allMetrics[$metrics['code']] = $this->updateMetrics($metrics);
            }
        }

        return $allMetrics;
    }

    /**
     * Converts 'lastBuildDate' value into a \DateTime instance if the date-time is valid, and unsets it otherwise.
     */
    private function updateMetrics(array $metrics): array
    {
        if (isset($metrics[self::LAST_BUILD_DATE])) {
            if (\is_string($metrics[self::LAST_BUILD_DATE])) {
                try {
                    $metrics[self::LAST_BUILD_DATE] = new \DateTime(
                        $metrics[self::LAST_BUILD_DATE],
                        new \DateTimeZone('UTC')
                    );
                } catch (\Exception $e) {
                    unset($metrics[self::LAST_BUILD_DATE]);
                }
            } elseif (!($metrics[self::LAST_BUILD_DATE] instanceof \DateTimeInterface)) {
                unset($metrics[self::LAST_BUILD_DATE]);
            }
        }

        return $metrics;
    }
}
