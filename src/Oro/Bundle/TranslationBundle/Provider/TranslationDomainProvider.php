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

    private ManagerRegistry $doctrine;
    private CacheInterface $cache;
    private ?array $availableDomains = null;

    public function __construct(ManagerRegistry $doctrine, CacheInterface $cache)
    {
        $this->doctrine = $doctrine;
        $this->cache = $cache;
    }

    /**
     * Gets the list of all translation domains.
     *
     * @return string[]
     */
    public function getAvailableDomains(): array
    {
        if (null === $this->availableDomains) {
            $this->availableDomains = $this->cache->get(self::AVAILABLE_DOMAINS_NODE, function () {
                return $this->getTranslationKeyRepository()->findAvailableDomains();
            });
        }

        return $this->availableDomains;
    }

    /**
     * Gets the list of all translation domains to use as the choice list in forms.
     *
     * @return array [domain => domain, ...]
     */
    public function getAvailableDomainChoices(): array
    {
        $availableDomains = $this->getAvailableDomains();

        return array_combine($availableDomains, $availableDomains);
    }

    public function clearCache(): void
    {
        $this->availableDomains = null;
        $this->cache->delete(self::AVAILABLE_DOMAINS_NODE);
    }

    private function getTranslationKeyRepository(): TranslationKeyRepository
    {
        return $this->doctrine->getRepository(TranslationKey::class);
    }
}
