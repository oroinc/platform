<?php

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\DoctrineAclCache;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

class AclCache extends DoctrineAclCache
{
    const ENTRY_CLASS = 'Symfony\Component\Security\Acl\Domain\Entry';

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var UnderlyingAclCache
     */
    protected $underlyingCache;

    /**
     * @param CacheProvider $cache
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param string $prefix
     */
    public function __construct(
        CacheProvider $cache,
        PermissionGrantingStrategyInterface $permissionGrantingStrategy,
        $prefix = DoctrineAclCache::PREFIX
    ) {
        $this->cache = $cache;
        $this->cache->setNamespace($prefix);
        parent::__construct($this->cache, $permissionGrantingStrategy, $prefix);
    }

    /**
     * Set Underlying cache
     *
     * @param UnderlyingAclCache $underlyingCache
     */
    public function setUnderlyingCache(UnderlyingAclCache $underlyingCache)
    {
        $this->underlyingCache = $underlyingCache;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache()
    {
        $this->cache->deleteAll();
        // we should clear underlying cache to avoid generation of wrong ACLs
        $this->underlyingCache->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function evictFromCacheByIdentity(ObjectIdentityInterface $oid)
    {
        if ($this->underlyingCache->isUnderlying($oid)) {
            $this->underlyingCache->evictFromCache($oid);
        }

        parent::evictFromCacheByIdentity($oid);
    }

    /**
     * {@inheritdoc}
     */
    public function putInCache(AclInterface $acl)
    {
        // get access to field aces in order to clone their identity
        // to prevent serialize/unserialize bug with few field aces per one sid

        $privatePropReader = function (Acl $acl, $field) {
            return $acl->$field;
        };
        $privatePropReader = \Closure::bind($privatePropReader, null, $acl);

        $aces = $privatePropReader($acl, 'classFieldAces');
        $aces = array_merge($aces, $privatePropReader($acl, 'objectFieldAces'));

        $privatePropWriter = function (FieldEntry $entry, $field, $value) {
            $entry->$field = $value;
        };

        foreach ($aces as $fieldAces) {
            /** @var FieldEntry $fieldEntry */
            foreach ($fieldAces as $fieldEntry) {
                $writeClosure = \Closure::bind($privatePropWriter, $fieldEntry, self::ENTRY_CLASS);
                $writeClosure($fieldEntry, 'securityIdentity', clone $fieldEntry->getSecurityIdentity());
            }
        }

        parent::putInCache($acl);
    }
}
