<?php

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Oro\Bundle\SecurityBundle\Acl\Event\CacheClearEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * ACL cache that stores ACL by OID and SIDs.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
    public function getFromCacheByIdentityAndSids(ObjectIdentityInterface $oid, array $sids): ?AclInterface
    {
        if (empty($sids)) {
            return null;
        }

        $key = $this->getDataKeyByIdentity($oid);
        // here we use -1 as ACL id because 0 used as empty id
        // (@see \Oro\Bundle\SecurityBundle\Acl\Dbal\AclProvider::EMPTY_ACL_ID)
        $acl = new Acl(-1, $oid, $this->permissionGrantingStrategy, $sids, false);

        $cacheKeys = [];
        $sidKeys = [];
        foreach ($sids as $sid) {
            $sidKey = $this->getSidKey($sid);
            $cacheKeys[] = $key . '_' . $sidKey;
            $sidKeys[$sidKey] = $sid;
        }

        $hasAces = false;
        $data = $this->cache->getItems($cacheKeys);
        $indexes = ['o' => 0, 'c' => 0, 'fo' => [], 'fc' => []];
        foreach ($data as $sidDataItem) {
            // we have no cache item for given SID and OID
            if (!$sidDataItem->isHit()) {
                return null;
            }
            $sidDataArray = $sidDataItem->get();
            if (empty($sidDataArray[1])) {
                continue;
            }

            $hasAces = true;
            [$sidKey, $aceData] = $sidDataArray;
            $this->unserialize($acl, $sidKeys[$sidKey], $aceData, $indexes);
        }

        if (!$hasAces) {
            // if each SID entry cache return empty result, we should return empty ACL (with id = 0)
            $acl = new Acl(0, $oid, $this->permissionGrantingStrategy, $sids, false);
        }
        return $acl;
    }

    /**
     * Stores a new ACL in the cache
     */
    public function putInCacheBySids(AclInterface $acl, array $sids): void
    {
        if (empty($sids)) {
            return;
        }

        $key = $this->getDataKeyByIdentity($acl->getObjectIdentity());
        $sidsItem =  $this->cache->getItem($key);
        $batchSidItems = $sidsItem->isHit() ? $sidsItem->get() : [];

        $hasChanges = false;
        [$classFieldAces, $objectFieldAces] = $this->getFieldAces($acl);
        foreach ($sids as $sid) {
            $sidKey = $this->getSidKey($sid);
            $itemKey = $key . '_' . $sidKey;
            if (!\array_key_exists($sidKey, $batchSidItems)) {
                $hasChanges = true;
                $batchSidItems[$sidKey] = true;
                $aceData = $this->serialize($acl, $sid, $classFieldAces, $objectFieldAces);
                $item = $this->cache->getItem($itemKey);
                $item->set([$sidKey, $aceData]);
                $this->cache->save($item);
            }
        }

        if ($hasChanges) {
            $sidsItem->set($batchSidItems);
            $this->cache->save($sidsItem);
        }
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
        $itemsToDelete = [$key];
        $sidsItems =  $this->cache->getItem($key);
        $batchSidItems = $sidsItems->isHit() ? $sidsItems->get() : [];
        foreach (array_keys($batchSidItems) as $batchSidItem) {
            $itemsToDelete[] = $key . '_' . $batchSidItem;
        }

        $this->cache->deleteItems($itemsToDelete);
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function unserialize(
        AclInterface $acl,
        SecurityIdentityInterface $sid,
        array $aceData,
        array &$indexes
    ): void {
        if (\array_key_exists('o', $aceData)) {
            foreach ($aceData['o'] as $aceInfo) {
                [$mask, $strategy] = $this->extractAceInfo($aceInfo);
                $acl->insertObjectAce($sid, $mask, $indexes['o'], true, $strategy);

                $indexes['o']++;
            }
        }

        if (\array_key_exists('c', $aceData)) {
            foreach ($aceData['c'] as $aceInfo) {
                [$mask, $strategy] = $this->extractAceInfo($aceInfo);
                $acl->insertClassAce($sid, $mask, $indexes['c'], true, $strategy);
                $indexes['c']++;
            }
        }

        if (\array_key_exists('fc', $aceData)) {
            foreach ($aceData['fc'] as $fieldName => $fieldAces) {
                $this->ensureFieldIndexExist($indexes['fc'], $fieldName);
                foreach ($fieldAces as $aceInfo) {
                    [$mask, $strategy] = $this->extractAceInfo($aceInfo);
                    $acl->insertClassFieldAce($fieldName, $sid, $mask, $indexes['fc'][$fieldName], true, $strategy);
                    $indexes['fc'][$fieldName]++;
                }
            }
        }

        if (\array_key_exists('fo', $aceData)) {
            foreach ($aceData['fo'] as $fieldName => $fieldAces) {
                $this->ensureFieldIndexExist($indexes['fo'], $fieldName);
                foreach ($fieldAces as $aceInfo) {
                    [$mask, $strategy] = $this->extractAceInfo($aceInfo);
                    $acl->insertObjectFieldAce($fieldName, $sid, $mask, $indexes['fo'][$fieldName], true, $strategy);
                    $indexes['fo'][$fieldName]++;
                }
            }
        }
    }

    /**
     * Extracts mask and strategy from ACE info.
     * Supports both old format (int mask) and new format (array with mask and strategy).
     */
    private function extractAceInfo(int|array $aceInfo): array
    {
        if (\is_array($aceInfo)) {
            return [$aceInfo['mask'], $aceInfo['strategy'] ?? 'all'];
        }

        // Backward compatibility: old cache format with only mask
        return [$aceInfo, 'all'];
    }

    private function ensureFieldIndexExist(&$array, string $fieldName): void
    {
        if (!array_key_exists($fieldName, $array)) {
            $array[$fieldName] = 0;
        }
    }

    private function serialize(
        AclInterface $acl,
        SecurityIdentityInterface $sid,
        array $classFieldAces,
        array $objectFieldAces
    ): array {
        $aceData = [];

        $aceDataSerialized = $this->serializeAces($acl->getClassAces(), $sid);
        if (!empty($aceDataSerialized)) {
            $aceData['c'] = $aceDataSerialized;
        }

        $aceDataSerialized = $this->serializeAces($acl->getObjectAces(), $sid);
        if (!empty($aceDataSerialized)) {
            $aceData['o'] = $aceDataSerialized;
        }

        foreach ($classFieldAces as $fieldName => $aces) {
            $aceDataSerialized = $this->serializeAces($aces, $sid);
            if (!empty($aceDataSerialized)) {
                $aceData['fc'][$fieldName] = $aceDataSerialized;
            }
        }

        foreach ($objectFieldAces as $fieldName => $aces) {
            $aceDataSerialized = $this->serializeAces($aces, $sid);
            if (!empty($aceDataSerialized)) {
                $aceData['fo'][$fieldName] = $aceDataSerialized;
            }
        }

        return $aceData;
    }

    private function serializeAces(array $aces, SecurityIdentityInterface $sid): array
    {
        $data = [];
        foreach ($aces as $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $data[] = [
                    'mask' => $ace->getMask(),
                    'strategy' => $ace->getStrategy(),
                ];
            }
        }

        return $data;
    }

    /**
     * Returns the key for the object identity.
     */
    private function getDataKeyByIdentity(ObjectIdentityInterface $oid): string
    {
        return md5($oid->getType()).sha1($oid->getType())
            .'_'.md5($oid->getIdentifier()).sha1($oid->getIdentifier());
    }

    private function getSidKey(SecurityIdentityInterface $sid): string
    {
        return md5('sid' . $this->sidConverter->convert($sid));
    }

    private function getFieldAces(AclInterface $acl): array
    {
        $privatePropReader = function (Acl $acl, $field) {
            return $acl->$field;
        };
        $privatePropReader = \Closure::bind($privatePropReader, null, $acl);

        $classFieldAces = $privatePropReader($acl, 'classFieldAces');
        $objectFieldAces = $privatePropReader($acl, 'objectFieldAces');

        return [$classFieldAces, $objectFieldAces];
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
}
