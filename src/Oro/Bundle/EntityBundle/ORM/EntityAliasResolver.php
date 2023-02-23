<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides functionality to get singular and plural aliases for an entity class
 * and resolve entity class by any of these aliases.
 */
class EntityAliasResolver implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    private const CACHE_KEY = 'entity_aliases';

    /** @var EntityAliasLoader */
    private $loader;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigCacheStateInterface */
    private $configCacheState;

    /** @var EntityAliasStorage|null */
    private $storage;

    public function __construct(EntityAliasLoader $loader, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Sets an object that should be used to check if entity alias cache is fresh or should be rebuilt.
     */
    public function setConfigCacheState(ConfigCacheStateInterface $configCacheState): void
    {
        $this->configCacheState = $configCacheState;
    }

    /**
     * Checks whether the given entity class has an alias.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return bool
     */
    public function hasAlias($entityClass)
    {
        $this->ensureAllAliasesLoaded();

        return null !== $this->storage->getEntityAlias($entityClass);
    }

    /**
     * Returns the alias for the given entity class.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return string The alias for the requested entity
     *
     * @throws EntityAliasNotFoundException if an alias not found
     */
    public function getAlias($entityClass)
    {
        $this->ensureAllAliasesLoaded();

        $entityAlias = $this->storage->getEntityAlias($entityClass);
        if (null === $entityAlias) {
            throw new EntityAliasNotFoundException(
                sprintf('An alias for "%s" entity not found.', $entityClass)
            );
        }

        return $entityAlias->getAlias();
    }

    /**
     * Returns the plural alias for the given entity class.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return string The plural alias for the requested entity
     *
     * @throws EntityAliasNotFoundException if an alias not found
     */
    public function getPluralAlias($entityClass)
    {
        $this->ensureAllAliasesLoaded();

        $entityAlias = $this->storage->getEntityAlias($entityClass);
        if (null === $entityAlias) {
            throw new EntityAliasNotFoundException(
                sprintf('A plural alias for "%s" entity not found.', $entityClass)
            );
        }

        return $entityAlias->getPluralAlias();
    }

    /**
     * Returns the entity class by the given alias.
     *
     * @param string $alias The alias of an entity
     *
     * @return string The FQCN of an entity
     *
     * @throws EntityAliasNotFoundException if the given alias is not associated with any entity class
     */
    public function getClassByAlias($alias)
    {
        $this->ensureAllAliasesLoaded();

        $entityClass = $this->storage->getClassByAlias($alias);
        if (!$entityClass) {
            throw new EntityAliasNotFoundException(
                sprintf('The alias "%s" is not associated with any entity class.', $alias)
            );
        }

        return $entityClass;
    }

    /**
     * Returns the entity class by the given plural alias.
     *
     * @param string $pluralAlias The plural alias of an entity
     *
     * @return string The FQCN of an entity
     *
     * @throws EntityAliasNotFoundException if the given plural alias is not associated with any entity class
     */
    public function getClassByPluralAlias($pluralAlias)
    {
        $this->ensureAllAliasesLoaded();

        $entityClass = $this->storage->getClassByPluralAlias($pluralAlias);
        if (!$entityClass) {
            throw new EntityAliasNotFoundException(
                sprintf('The plural alias "%s" is not associated with any entity class.', $pluralAlias)
            );
        }

        return $entityClass;
    }

    /**
     * Returns all entity aliases.
     *
     * @return EntityAlias[]
     */
    public function getAll()
    {
        $this->ensureAllAliasesLoaded();

        return $this->storage->getAll();
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->clearCache();
        $this->ensureAllAliasesLoaded();
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
        $this->storage = null;
    }

    /**
     * Creates a new instance of EntityAliasStorage.
     *
     * @return EntityAliasStorage
     */
    protected function createStorage()
    {
        return new EntityAliasStorage();
    }

    /**
     * Makes sure that aliases for all entities are loaded.
     */
    private function ensureAllAliasesLoaded(): void
    {
        if (null === $this->storage) {
            $storage = $this->fetchAliasesFromCache();
            if (null === $storage) {
                $storage = $this->loadAliases();
                if (null !== $storage) {
                    $this->saveAliasesToCache($storage);
                }
            }
            $this->storage = $storage;
        }
    }

    private function fetchAliasesFromCache(): ?EntityAliasStorage
    {
        $storage = null;
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);
        if ($cacheItem->isHit()) {
            [$timestamp, $value] = $cacheItem->get();
            if (null === $this->configCacheState || $this->configCacheState->isCacheFresh($timestamp)) {
                $storage = $value;
            }
        }

        return $storage;
    }

    private function saveAliasesToCache(EntityAliasStorage $storage): void
    {
        $timestamp = $this->configCacheState?->getCacheTimestamp();
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);
        $cacheItem->set([$timestamp, $storage]);
        $this->cache->save($cacheItem);
    }

    private function loadAliases(): ?EntityAliasStorage
    {
        $storage = $this->createStorage();
        try {
            $this->loader->load($storage);
        } catch (InvalidEntityAliasException $e) {
            throw $e;
        } catch (\Exception $e) {
            $storage = null;
            $this->logger->error('Loading of entity aliases failed', ['exception' => $e]);
        }

        return $storage;
    }
}
