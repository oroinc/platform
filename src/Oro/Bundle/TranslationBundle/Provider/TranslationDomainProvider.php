<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This class is responsible to get available translation domains.
 */
class TranslationDomainProvider
{
    private const AVAILABLE_DOMAINS_NODE = 'availableDomains';

    protected ManagerRegistry $registry;
    protected CacheInterface $cache;
    protected ?array $availableDomains = null;

    public function __construct(ManagerRegistry $registry, CacheInterface $cache)
    {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    /**
     * Returns the list of all existing in the database translation domains.
     *
     * @return array ['domain' => '...']
     */
    public function getAvailableDomains(): array
    {
        $this->ensureAvailableDomainsLoaded();

        return $this->availableDomains;
    }

    /**
     * Returns the list of all existing in the database translation domains for the given locales.
     *
     * @return array [['code' = '...', 'domain' => '...'], ...]
     */
    public function getAvailableDomainsForLocales(array $locales): array
    {
        $this->ensureAvailableDomainsLoaded();

        $result = [];
        foreach ($locales as $locale) {
            foreach ($this->availableDomains as $domain) {
                $result[] = ['code' => $locale, 'domain' => $domain];
            }
        }

        return $result;
    }

    public function clearCache(): TranslationDomainProvider
    {
        $this->availableDomains = null;
        $this->cache->delete(self::AVAILABLE_DOMAINS_NODE);

        return $this;
    }

    protected function ensureAvailableDomainsLoaded(): void
    {
        if (null !== $this->availableDomains) {
            return;
        }

        $this->availableDomains = $this->cache->get(self::AVAILABLE_DOMAINS_NODE, function () {
            return $this->getTranslationKeyRepository()->findAvailableDomains();
        });
    }

    private function getTranslationKeyRepository(): TranslationKeyRepository
    {
        return $this->registry->getManagerForClass(TranslationKey::class)->getRepository(TranslationKey::class);
    }
}
