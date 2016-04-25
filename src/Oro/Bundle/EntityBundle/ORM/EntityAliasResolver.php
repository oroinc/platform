<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;

class EntityAliasResolver
{
    const CACHE_KEY = 'entity_aliases';

    /** @var EntityAliasLoader */
    protected $loader;

    /** @var Cache */
    protected $cache;

    /** @var bool */
    protected $debug;

    /** @var EntityAliasStorage|null */
    private $storage;

    /**
     * @param EntityAliasLoader $loader
     * @param Cache             $cache
     * @param bool              $debug
     */
    public function __construct(EntityAliasLoader $loader, Cache $cache, $debug)
    {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->debug = $debug;
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
     * Returns all entity aliases
     *
     * @return EntityAlias[]
     */
    public function getAll()
    {
        $this->ensureAllAliasesLoaded();

        return $this->storage->getAll();
    }

    /**
     * Warms up the cache.
     */
    public function warmUpCache()
    {
        $this->clearCache();
        $this->ensureAllAliasesLoaded();
    }

    /**
     * Clears the cache.
     */
    public function clearCache()
    {
        $this->cache->delete(self::CACHE_KEY);
        $this->storage = null;
    }

    /**
     * Makes sure that aliases for all entities are loaded
     */
    protected function ensureAllAliasesLoaded()
    {
        if (null === $this->storage) {
            $cachedData = $this->cache->fetch(self::CACHE_KEY);
            if (false !== $cachedData) {
                $this->storage = $cachedData;
                $this->storage->setDebug($this->debug);
            } else {
                $this->storage = new EntityAliasStorage();
                $this->storage->setDebug($this->debug);
                $this->loader->load($this->storage);
                $this->cache->save(self::CACHE_KEY, $this->storage);
            }
        }
    }
}
