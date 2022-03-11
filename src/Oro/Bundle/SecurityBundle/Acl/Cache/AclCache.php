<?php

/*
 * This file is a copy of {@see \Symfony\Component\Security\Acl\Domain\DoctrineAclCache}.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

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
use Symfony\Contracts\Cache\CacheInterface;

/**
 * ACL cache that stores ACL by OID and SIDs.
 */
class AclCache implements AclCacheInterface
{
    private CacheInterface $cache;
    private PermissionGrantingStrategyInterface $permissionGrantingStrategy;
    private UnderlyingAclCache $underlyingCache;
    private EventDispatcherInterface $eventDispatcher;
    private SecurityIdentityToStringConverterInterface $sidConverter;

    public function __construct(
        CacheInterface $cache,
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
     * Retrieves an ACL for the given object identity from the cache
     */
    public function getFromCacheByIdentityAndSids(ObjectIdentityInterface $oid, array $sids):? AclInterface
    {
        $key = $this->getDataKeyByIdentity($oid);
        $sidKey = $this->getSidKey($sids);

        $data =  $this->cache->get($key, function () {
            return null;
        });
        if ($data && array_key_exists($sidKey, $data)) {
            return $this->unserializeAcl($data[$sidKey]);
        }

        return null;
    }

    /**
     * Stores a new ACL in the cache
     */
    public function putInCacheBySids(AclInterface $acl, array $sids): void
    {
        $acl = $this->fixAclAces($acl);

        $key = $this->getDataKeyByIdentity($acl->getObjectIdentity());
        $sidKey = $this->getSidKey($sids);

        $this->cache->delete($key);
        $this->cache->get($key, function () use ($sidKey, $acl) {
            return [$sidKey => \serialize($acl)];
        });
    }

    /**
     * Removes an ACL from the cache by the reference OID.
     */
    public function evictFromCacheByIdentity(ObjectIdentityInterface $oid): void
    {
        if ($this->underlyingCache->isUnderlying($oid)) {
            $this->underlyingCache->evictFromCache($oid);
        }

        $key = $this->getDataKeyByIdentity($oid);

        $this->cache->delete($key);
    }

    /**
     * Removes all ACLs from the cache.
     */
    public function clearCache(): void
    {
        if ($this->cache instanceof CacheInterface) {
            $this->cache->clear();
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
        return md5($oid->getType()).sha1($oid->getType())
            .'_'.md5($oid->getIdentifier()).sha1($oid->getIdentifier());
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
