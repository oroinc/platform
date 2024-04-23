<?php

/*
 * This file is a copy of {@see \Symfony\Component\Security\Acl\Domain\DoctrineAclCache}.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Oro\Bundle\SecurityBundle\Acl\Event\CacheClearEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * ACL cache that stores ACL by OID and SIDs.
 */
class AclCache implements AclCacheInterface
{
    /** @var Cache */
    private $cache;

    /** @var PermissionGrantingStrategyInterface */
    private $permissionGrantingStrategy;

    /** @var UnderlyingAclCache */
    private $underlyingCache;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var SecurityIdentityToStringConverterInterface */
    private $sidConverter;

    public function __construct(
        Cache $cache,
        PermissionGrantingStrategyInterface $permissionGrantingStrategy,
        UnderlyingAclCache $underlyingCache,
        EventDispatcherInterface $eventDispatcher,
        SecurityIdentityToStringConverterInterface $sidConverter
    ) {
        $this->cache = $cache;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->underlyingCache = $underlyingCache;
        $this->eventDispatcher = $eventDispatcher;
        $this->sidConverter = $sidConverter;
    }

    /**
     * Retrieves an ACL for the given object identity from the cache.
     *
     * @param ObjectIdentityInterface     $oid
     * @param SecurityIdentityInterface[] $sids
     *
     * @return AclInterface
     */
    public function getFromCacheByIdentityAndSids(ObjectIdentityInterface $oid, array $sids):? AclInterface
    {
        $key = $this->getDataKeyByIdentity($oid);
        $sidKey = $this->getSidKey($sids);
        $cacheKey = $key . '_' . $sidKey;

        $data = $this->cache->fetch($cacheKey);
        if (false !== $data) {
            return $this->unserializeAcl($data);
        }

        return null;
    }

    /**
     * Stores a new ACL in the cache.
     *
     * @param AclInterface                $acl
     * @param SecurityIdentityInterface[] $sids
     */
    public function putInCacheBySids(AclInterface $acl, array $sids)
    {
        $acl = $this->fixAclAces($acl);

        $key = $this->getDataKeyByIdentity($acl->getObjectIdentity());
        $sidKey = $this->getSidKey($sids);
        $itemKey = $key . '_' . $sidKey;

        $this->cache->save($itemKey, \serialize($acl));

        $sidsItem =  $this->cache->fetch($key);
        if (false === $sidsItem) {
            $sidsItem = [];
        }
        if (!\array_key_exists($sidKey, $sidsItem)) {
            $sidsItem[$sidKey] = true;
            $this->cache->save($key, $sidsItem);
        }
    }

    /**
     * Removes an ACL from the cache by the reference OID.
     */
    public function evictFromCacheByIdentity(ObjectIdentityInterface $oid)
    {
        if ($this->underlyingCache->isUnderlying($oid)) {
            $this->underlyingCache->evictFromCache($oid);
        }

        $key = $this->getDataKeyByIdentity($oid);
        if (!$this->cache->contains($key)) {
            return;
        }

        $sidsItems =  $this->cache->fetch($key);
        if (false === $sidsItems) {
            $sidsItems = [];
        }
        foreach (array_keys($sidsItems) as $batchSidItem) {
            $this->cache->delete($key . '_' . $batchSidItem);
        }

        $this->cache->delete($key);
    }

    /**
     * Removes all ACLs from the cache.
     */
    public function clearCache()
    {
        if ($this->cache instanceof CacheProvider) {
            $this->cache->deleteAll();
        }

        // we should clear underlying cache to avoid generation of wrong ACLs
        $this->underlyingCache->clearCache();
        $this->eventDispatcher->dispatch(new CacheClearEvent(), CacheClearEvent::CACHE_CLEAR_EVENT);
    }

    /**
     * Returns the key for the object identity.
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return string
     */
    private function getDataKeyByIdentity(ObjectIdentityInterface $oid)
    {
        return md5($oid->getType())
            .'_'.md5($oid->getIdentifier());
    }

    /**
     * @param SecurityIdentityInterface[] $sids
     *
     * @return string
     */
    private function getSidKey(array $sids): string
    {
        $sidsString = 'sid';
        foreach ($sids as $sid) {
            $sidsString .= $this->sidConverter->convert($sid);
        }

        return md5($sidsString);
    }

    /**
     * Unserializes the ACL.
     *
     * @param string $serialized
     *
     * @return AclInterface
     */
    private function unserializeAcl($serialized)
    {
        $acl = unserialize($serialized);

        $reflectionProperty = new \ReflectionProperty($acl, 'permissionGrantingStrategy');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($acl, $this->permissionGrantingStrategy);
        $reflectionProperty->setAccessible(false);

        $aceAclProperty = new \ReflectionProperty(Entry::class, 'acl');
        $aceAclProperty->setAccessible(true);

        foreach ($acl->getObjectAces() as $ace) {
            $aceAclProperty->setValue($ace, $acl);
        }
        foreach ($acl->getClassAces() as $ace) {
            $aceAclProperty->setValue($ace, $acl);
        }

        $aceClassFieldProperty = new \ReflectionProperty($acl, 'classFieldAces');
        $aceClassFieldProperty->setAccessible(true);
        foreach ($aceClassFieldProperty->getValue($acl) as $aces) {
            foreach ($aces as $ace) {
                $aceAclProperty->setValue($ace, $acl);
            }
        }
        $aceClassFieldProperty->setAccessible(false);

        $aceObjectFieldProperty = new \ReflectionProperty($acl, 'objectFieldAces');
        $aceObjectFieldProperty->setAccessible(true);
        foreach ($aceObjectFieldProperty->getValue($acl) as $aces) {
            foreach ($aces as $ace) {
                $aceAclProperty->setValue($ace, $acl);
            }
        }
        $aceObjectFieldProperty->setAccessible(false);

        $aceAclProperty->setAccessible(false);

        return $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function fixAclAces(AclInterface $acl)
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
                $writeClosure = \Closure::bind($privatePropWriter, $fieldEntry, Entry::class);
                $writeClosure($fieldEntry, 'securityIdentity', clone $fieldEntry->getSecurityIdentity());
            }
        }

        return $acl;
    }

    /**
     * {@inheritDoc}
     */
    public function evictFromCacheById($primaryKey)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getFromCacheById($primaryKey)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getFromCacheByIdentity(ObjectIdentityInterface $oid)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function putInCache(AclInterface $acl)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
