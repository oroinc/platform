<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

/**
 * This class is responsible to get available translation domains.
 */
class TranslationDomainProvider
{
    const AVAILABLE_DOMAINS_NODE = 'availableDomains';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $availableDomains;

    /**
     * @param ManagerRegistry $registry
     * @param CacheProvider $cache
     */
    public function __construct(ManagerRegistry $registry, CacheProvider $cache)
    {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    /**
     * Returns the list of all existing in the database translation domains.
     *
     * @return array ['domain' => '...']
     */
    public function getAvailableDomains()
    {
        $this->ensureAvailableDomainsLoaded();

        return $this->availableDomains;
    }

    /**
     * Returns the list of all existing in the database translation domains for the given locales.
     *
     * @param string[] $locales
     * @return array [['code' = '...', 'domain' => '...'], ...]
     */
    public function getAvailableDomainsForLocales(array $locales)
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

    /**
     * @return $this
     */
    public function clearCache()
    {
        $this->availableDomains = null;
        $this->cache->delete(self::AVAILABLE_DOMAINS_NODE);

        return $this;
    }

    protected function ensureAvailableDomainsLoaded()
    {
        if (null !== $this->availableDomains) {
            return;
        }

        $availableDomains = $this->cache->fetch(self::AVAILABLE_DOMAINS_NODE);
        if (false === $availableDomains) {
            $availableDomains = $this->getTranslationKeyRepository()->findAvailableDomains();
            $this->cache->save(self::AVAILABLE_DOMAINS_NODE, $availableDomains);
        }
        $this->availableDomains = $availableDomains;
    }

    /**
     * @return TranslationKeyRepository
     */
    private function getTranslationKeyRepository()
    {
        return $this->registry->getManagerForClass(TranslationKey::class)->getRepository(TranslationKey::class);
    }
}
