<?php

/*
 * This file is a copy of {@see \Symfony\Component\Security\Acl\Dbal\AclProvider}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\SecurityBundle\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An ACL provider implementation.
 *
 * This provider assumes that all ACLs share the same PermissionGrantingStrategy.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclProvider implements AclProviderInterface
{
    const MAX_BATCH_SIZE = 30;

    protected const EMPTY_ACL_ID = 0;

    /** @var Connection */
    protected $connection;

    /** @var PermissionGrantingStrategyInterface */
    protected $permissionGrantingStrategy;

    /** @var array */
    protected $options;

    /** @var AclCache|null */
    protected $cache;

    /** @var SecurityIdentityToStringConverterInterface */
    private $sidConverter;

    /** @var array [oid key => [sid key => acl, ...], ...] */
    protected $loadedAcls = [];

    /** @var \SplObjectStorage[] [ace id => SplObjectStorage (acl => ace, ...), ...] */
    protected $loadedAces = [];

    /** @var array [oid key => [sid key => bool, ...], ...] */
    protected $notFoundAcls = [];

    /**
     * @param Connection                          $connection
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array                               $options
     * @param AclCache                            $cache
     */
    public function __construct(
        Connection $connection,
        PermissionGrantingStrategyInterface $permissionGrantingStrategy,
        array $options,
        AclCache $cache = null
    ) {
        $this->connection = $connection;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->options = $options;
        $this->cache = $cache;
    }

    public function setSecurityIdentityToStringConverter(SecurityIdentityToStringConverterInterface $converter): void
    {
        $this->sidConverter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren(ObjectIdentityInterface $parentOid, $directChildrenOnly = false)
    {
        [$sql, $params, $types] = $this->getFindChildrenSql($parentOid, $directChildrenOnly);

        $children = [];
        foreach ($this->connection->executeQuery($sql, $params, $types)->fetchAll() as $data) {
            $children[] = new ObjectIdentity($data['object_identifier'], $data['class_type']);
        }

        return $children;
    }

    /**
     * {@inheritdoc}
     */
    public function findAcl(ObjectIdentityInterface $oid, array $sids = [])
    {
        return $this->findAcls([$oid], $sids)->offsetGet($oid);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function findAcls(array $oids, array $sids = [])
    {
        $result = new \SplObjectStorage();
        $currentBatch = [];
        $oidLookup = [];
        $sidKey = $this->getSidKey($sids);

        for ($i = 0, $c = count($oids); $i < $c; ++$i) {
            $oid = $oids[$i];
            $oidKey = $this->getOidKey($oid->getType(), $oid->getIdentifier());
            $oidLookup[$oidKey] = $oid;
            $aclFound = false;

            // check if result already contains an ACL
            if ($result->contains($oid)) {
                $aclFound = true;
            }

            // check if this ACL has already been hydrated
            if (!$aclFound && isset($this->loadedAcls[$oidKey][$sidKey])) {
                $acl = $this->loadedAcls[$oidKey][$sidKey];

                if (!$acl->isSidLoaded($sids)) {
                    // FIXME: we need to load ACEs for the missing SIDs. This is never
                    //        reached by the default implementation, since we do not
                    //        filter by SID
                    throw new \RuntimeException('This is not supported by the default implementation.');
                } else {
                    $result->attach($oid, $acl);
                    $aclFound = true;
                }
            }

            // check if we can locate the ACL in the cache
            if (!$aclFound && null !== $this->cache) {
                $acl = $this->cache->getFromCacheByIdentityAndSids($oid, $sids);

                if (null !== $acl) {
                    if ($acl->isSidLoaded($sids)) {
                        // check if any of the parents has been loaded since we need to
                        // ensure that there is only ever one ACL per object identity
                        $parentAcl = $acl->getParentAcl();
                        while (null !== $parentAcl) {
                            $parentOidKey = $this->getOidKey(
                                $parentAcl->getObjectIdentity()->getType(),
                                $parentAcl->getObjectIdentity()->getIdentifier()
                            );
                            if (isset($this->loadedAcls[$parentOidKey][$sidKey])) {
                                $acl->setParentAcl($this->loadedAcls[$parentOidKey][$sidKey]);
                                break;
                            } else {
                                $this->loadedAcls[$parentOidKey][$sidKey] = $parentAcl;
                                $this->updateAceIdentityMap($parentAcl);
                            }
                            $parentAcl = $parentAcl->getParentAcl();
                        }

                        $this->loadedAcls[$oidKey][$sidKey] = $acl;
                        $this->updateAceIdentityMap($acl);
                        $result->attach($oid, $acl);
                        $aclFound = true;
                    } else {
                        $this->cache->evictFromCacheByIdentity($oid);

                        foreach ($this->findChildren($oid) as $childOid) {
                            $this->cache->evictFromCacheByIdentity($childOid);
                        }
                    }
                }
            }

            // looks like we have to load the ACL from the database
            if (!$aclFound && empty($this->notFoundAcls[$oidKey][$sidKey])) {
                $currentBatch[] = $oid;
            }

            // Is it time to load the current batch?
            $currentBatchesCount = count($currentBatch);
            if ($currentBatchesCount > 0 && (self::MAX_BATCH_SIZE === $currentBatchesCount || ($i + 1) === $c)) {
                try {
                    $loadedBatch = $this->lookupObjectIdentities($currentBatch, $sids, $oidLookup);
                } catch (AclNotFoundException $e) {
                    $this->notFoundAcls[$oidKey][$sidKey] = true;
                    if ($result->count() === 0) {
                        throw $e;
                    }
                    throw $this->createNotAllAclsFoundException($result);
                }
                foreach ($currentBatch as $oid) {
                    $loadedAcl = $loadedBatch->offsetExists($oid)
                        ? $loadedBatch->offsetGet($oid)
                        : $this->createEmptyAcl($oid);

                    if (null !== $this->cache) {
                        $this->cache->putInCacheBySids($loadedAcl, $sids);
                    }

                    if (isset($oidLookup[$this->getOidKey($oid->getType(), $oid->getIdentifier())])) {
                        $result->attach($oid, $loadedAcl);
                    }
                }

                $currentBatch = [];
            }
        }

        // check that we got ACLs for all the identities
        foreach ($oids as $oid) {
            if (!$result->contains($oid)) {
                $oidKey = $this->getOidKey($oid->getType(), $oid->getIdentifier());
                $this->notFoundAcls[$oidKey][$sidKey] = true;
                if (1 === count($oids)) {
                    throw new AclNotFoundException(sprintf(
                        'No ACL found for %s.',
                        method_exists($oid, '__toString') ? $oid : get_class($oid)
                    ));
                }
                throw $this->createNotAllAclsFoundException($result);
            }
        }

        return $result;
    }

    /**
     * Constructs the query used for looking up object identities and associated
     * ACEs, and security identities.
     *
     * @param array $ancestorIds
     *
     * @return array [sql, param values, param types]
     */
    protected function getLookupSql(array $ancestorIds)
    {
        // FIXME: add support for filtering by sids (right now we select all sids)

        return [
            sprintf(
                'SELECT o.id as acl_id, o.object_identifier, o.parent_object_identity_id, o.entries_inheriting,'
                . ' c.class_type, e.id as ace_id, e.object_identity_id, e.field_name, e.ace_order, e.mask,'
                . ' e.granting, e.granting_strategy, e.audit_success, e.audit_failure, s.username,'
                . ' s.identifier as security_identifier FROM %s o INNER JOIN %s c ON c.id = o.class_id'
                . ' LEFT JOIN %s e ON e.class_id = o.class_id AND (e.object_identity_id = o.id OR %s)'
                . ' LEFT JOIN %s s ON s.id = e.security_identity_id WHERE o.id in (?)',
                $this->options['oid_table_name'],
                $this->options['class_table_name'],
                $this->options['entry_table_name'],
                $this->connection->getDatabasePlatform()->getIsNullExpression('e.object_identity_id'),
                $this->options['sid_table_name']
            ),
            [$ancestorIds],
            [Connection::PARAM_INT_ARRAY]
        ];
    }

    /**
     * @param array $batch
     *
     * @return array [sql, param values, param types]
     */
    protected function getAncestorLookupSql(array $batch)
    {
        $parameters = [];
        $parametersTypes = [];

        $sql = sprintf(
            'SELECT a.ancestor_id FROM %s o INNER JOIN %s c ON c.id = o.class_id'
            . ' INNER JOIN %s a ON a.object_identity_id = o.id WHERE ',
            $this->options['oid_table_name'],
            $this->options['class_table_name'],
            $this->options['oid_ancestors_table_name']
        );

        $types = [];
        $count = count($batch);
        for ($i = 0; $i < $count; ++$i) {
            if (!isset($types[$batch[$i]->getType()])) {
                $types[$batch[$i]->getType()] = true;

                // if there is more than one type we can safely break out of the
                // loop, because it is the differentiator factor on whether to
                // query for only one or more class types
                if (count($types) > 1) {
                    break;
                }
            }
        }

        if (1 === count($types)) {
            $ids = [];
            for ($i = 0; $i < $count; ++$i) {
                $ids[] = (string) $batch[$i]->getIdentifier();
            }

            $sql .= '(o.object_identifier IN (?) AND c.class_type = ?)';
            $parameters = [$ids, $batch[0]->getType()];
            $parametersTypes = [Connection::PARAM_STR_ARRAY, ParameterType::STRING];
        } else {
            for ($i = 0; $i < $count; ++$i) {
                $sql .= '(o.object_identifier = ? AND c.class_type = ?)';

                if ($i + 1 < $count) {
                    $sql .= ' OR ';
                }
                $parameters[] = $batch[$i]->getIdentifier();
                $parametersTypes[] = ParameterType::STRING;
                $parameters[] = $batch[$i]->getType();
                $parametersTypes[] = ParameterType::STRING;
            }
        }

        return [$sql, $parameters, $parametersTypes];
    }

    /**
     * Constructs the SQL for retrieving child object identities for the given
     * object identities.
     *
     * @param ObjectIdentityInterface $oid
     * @param bool                    $directChildrenOnly
     *
     * @return array [sql, param values, param types]
     */
    protected function getFindChildrenSql(ObjectIdentityInterface $oid, $directChildrenOnly)
    {
        if (false === $directChildrenOnly) {
            $query = sprintf(
                'SELECT o.object_identifier, c.class_type FROM %s o'
                . ' INNER JOIN %s c ON c.id = o.class_id INNER JOIN %s a ON a.object_identity_id = o.id'
                . ' WHERE a.ancestor_id = ? AND a.object_identity_id != a.ancestor_id',
                $this->options['oid_table_name'],
                $this->options['class_table_name'],
                $this->options['oid_ancestors_table_name']
            );
        } else {
            $query = sprintf(
                'SELECT o.object_identifier, c.class_type FROM %s o'
                . ' INNER JOIN %s c ON c.id = o.class_id WHERE o.parent_object_identity_id = ?',
                $this->options['oid_table_name'],
                $this->options['class_table_name']
            );
        }

        return [$query, [$this->retrieveObjectIdentityPrimaryKey($oid)], [ParameterType::INTEGER]];
    }

    /**
     * Constructs the SQL for retrieving the primary key of the given object
     * identity.
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return array [sql, param values, param types]
     */
    protected function getSelectObjectIdentityIdSql(ObjectIdentityInterface $oid)
    {
        return [
            sprintf(
                'SELECT o.id FROM %s o INNER JOIN %s c ON c.id = o.class_id'
                . ' WHERE o.object_identifier = ? AND c.class_type = ?',
                $this->options['oid_table_name'],
                $this->options['class_table_name']
            ),
            [$oid->getIdentifier(), $oid->getType()],
            [ParameterType::STRING, ParameterType::STRING]
        ];
    }

    /**
     * Returns the primary key of the passed object identity.
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return int
     */
    final protected function retrieveObjectIdentityPrimaryKey(ObjectIdentityInterface $oid)
    {
        [$sql, $params, $types] = $this->getSelectObjectIdentityIdSql($oid);

        return $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * This method is called when an ACL instance is retrieved from the cache.
     */
    private function updateAceIdentityMap(AclInterface $acl)
    {
        foreach (['classAces', 'classFieldAces', 'objectAces', 'objectFieldAces'] as $property) {
            $reflection = new \ReflectionProperty($acl, $property);
            $reflection->setAccessible(true);
            $value = $reflection->getValue($acl);

            if ('classAces' === $property || 'objectAces' === $property) {
                $this->doUpdateAceIdentityMap($value);
            } else {
                foreach ($value as $field => $aces) {
                    $this->doUpdateAceIdentityMap($value[$field]);
                }
            }

            $reflection->setValue($acl, $value);
            $reflection->setAccessible(false);
        }
    }

    /**
     * Retrieves all the ids which need to be queried from the database
     * including the ids of parent ACLs.
     *
     * @param array $batch
     *
     * @return array
     */
    private function getAncestorIds(array $batch)
    {
        [$sql, $params, $types] = $this->getAncestorLookupSql($batch);

        $ancestorIds = [];
        foreach ($this->connection->executeQuery($sql, $params, $types)->fetchAll() as $data) {
            // FIXME: skip ancestors which are cached
            // Fix: Oracle returns keys in uppercase
            $ancestorIds[] = reset($data);
        }

        return $ancestorIds;
    }

    /**
     * Does either overwrite the passed ACE, or saves it in the global identity
     * map to ensure every ACE only gets instantiated once.
     *
     * @param EntryInterface[] $aces
     */
    private function doUpdateAceIdentityMap(array &$aces)
    {
        foreach ($aces as $index => $ace) {
            $aceId = $ace->getId();
            $acl = $ace->getAcl();
            if (isset($this->loadedAces[$aceId])) {
                $loadedAces = $this->loadedAces[$aceId];
                if ($loadedAces->contains($acl)) {
                    $aces[$index] = $loadedAces->offsetGet($acl);
                } else {
                    $loadedAces->attach($acl, $ace);
                }
            } else {
                $loadedAces = new \SplObjectStorage();
                $loadedAces->attach($acl, $ace);
                $this->loadedAces[$aceId] = $loadedAces;
            }
        }
    }

    /**
     * This method is called for object identities which could not be retrieved
     * from the cache, and for which thus a database query is required.
     *
     * @param array $batch
     * @param array $sids
     * @param array $oidLookup
     *
     * @return \SplObjectStorage mapping object identities to ACL instances
     *
     * @throws AclNotFoundException
     */
    private function lookupObjectIdentities(array $batch, array $sids, array $oidLookup)
    {
        $ancestorIds = $this->getAncestorIds($batch);
        if (!$ancestorIds) {
            throw new AclNotFoundException('There is no ACL for the given object identity.');
        }

        [$sql, $params, $types] = $this->getLookupSqlBySids($ancestorIds, $sids);
        $stmt = $this->connection->executeQuery($sql, $params, $types);

        return $this->hydrateObjectIdentities($stmt, $oidLookup, $sids);
    }

    /**
     * This method is called to hydrate ACLs and ACEs.
     *
     * This method was designed for performance; thus, a lot of code has been
     * inlined at the cost of readability, and maintainability.
     *
     * Keep in mind that changes to this method might severely reduce the
     * performance of the entire ACL system.
     *
     * @param Statement $stmt
     * @param array     $oidLookup
     * @param array     $sids
     *
     * @return \SplObjectStorage
     *
     * @throws \RuntimeException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function hydrateObjectIdentities(Statement $stmt, array $oidLookup, array $sids)
    {
        $parentIdToFill = new \SplObjectStorage();
        $acls = $aces = $emptyArray = [];
        $oidCache = $oidLookup;
        $result = new \SplObjectStorage();
        $sidKey = $this->getSidKey($sids);

        // we need these to set protected properties on hydrated objects
        $aclReflection = new \ReflectionClass(Acl::class);
        $aclClassAcesProperty = $aclReflection->getProperty('classAces');
        $aclClassAcesProperty->setAccessible(true);
        $aclClassFieldAcesProperty = $aclReflection->getProperty('classFieldAces');
        $aclClassFieldAcesProperty->setAccessible(true);
        $aclObjectAcesProperty = $aclReflection->getProperty('objectAces');
        $aclObjectAcesProperty->setAccessible(true);
        $aclObjectFieldAcesProperty = $aclReflection->getProperty('objectFieldAces');
        $aclObjectFieldAcesProperty->setAccessible(true);
        $aclParentAclProperty = $aclReflection->getProperty('parentAcl');
        $aclParentAclProperty->setAccessible(true);

        // fetchAll() consumes more memory than consecutive calls to fetch(),
        // but it is faster
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $data) {
            [
                $aclId,
                $objectIdentifier,
                $parentObjectIdentityId,
                $entriesInheriting,
                $classType,
                $aceId,
                $objectIdentityId,
                $fieldName,
                $aceOrder,
                $mask,
                $granting,
                $grantingStrategy,
                $auditSuccess,
                $auditFailure,
                $username,
                $securityIdentifier
            ] = array_values($data);
            $oidKey = $this->getOidKey($classType, $objectIdentifier);

            // has the ACL been hydrated during this hydration cycle?
            if (isset($acls[$aclId])) {
                $acl = $acls[$aclId];
            // has the ACL been hydrated during any previous cycle, or was possibly loaded
            // from cache?
            } elseif (isset($this->loadedAcls[$oidKey][$sidKey])) {
                $acl = $this->loadedAcls[$oidKey][$sidKey];

                // keep reference in local array (saves us some hash calculations)
                $acls[$aclId] = $acl;

                // attach ACL to the result set; even though we do not enforce that every
                // object identity has only one instance, we must make sure to maintain
                // referential equality with the oids passed to findAcls()
                $oidCacheKey = $objectIdentifier.$classType;
                if (!isset($oidCache[$oidCacheKey])) {
                    $oidCache[$oidCacheKey] = $acl->getObjectIdentity();
                }
                $result->attach($oidCache[$oidCacheKey], $acl);
            // so, this hasn't been hydrated yet
            } else {
                // create object identity if we haven't done so yet
                if (!isset($oidCache[$oidKey])) {
                    $oidCache[$oidKey] = new ObjectIdentity($objectIdentifier, $classType);
                }

                $acl = new Acl(
                    (int) $aclId,
                    $oidCache[$oidKey],
                    $this->permissionGrantingStrategy,
                    $emptyArray,
                    (bool) $entriesInheriting
                );

                // keep a local, and global reference to this ACL
                $this->loadedAcls[$oidKey][$sidKey] = $acl;
                $acls[$aclId] = $acl;

                // try to fill in parent ACL, or defer until all ACLs have been hydrated
                if (null !== $parentObjectIdentityId) {
                    if (isset($acls[$parentObjectIdentityId])) {
                        $aclParentAclProperty->setValue($acl, $acls[$parentObjectIdentityId]);
                    } else {
                        $parentIdToFill->attach($acl, $parentObjectIdentityId);
                    }
                }

                $result->attach($oidCache[$oidKey], $acl);
            }

            // check if this row contains an ACE record
            if (null !== $aceId) {
                // have we already hydrated ACEs for this ACL?
                if (!isset($aces[$aclId])) {
                    $aces[$aclId] = [$emptyArray, $emptyArray, $emptyArray, $emptyArray];
                }

                // has this ACE already been hydrated during a previous cycle, or
                // possible been loaded from cache?
                // It is important to only ever have one ACE instance per actual row since
                // some ACEs are shared between ACL instances
                if (!isset($this->loadedAces[$aceId])) {
                    $this->loadedAces[$aceId] = new \SplObjectStorage();
                }
                $loadedAces = $this->loadedAces[$aceId];
                if (!$loadedAces->contains($acl)) {
                    $key = ($username ? '1' : '0') . $securityIdentifier;
                    if (!isset($sids[$key])) {
                        $sids[$key] = $this->buildSecurityIdentity($securityIdentifier, $username);
                    }

                    if (null === $fieldName) {
                        $loadedAces->attach($acl, new Entry(
                            (int) $aceId,
                            $acl,
                            $sids[$key],
                            $grantingStrategy,
                            (int) $mask,
                            (bool) $granting,
                            (bool) $auditFailure,
                            (bool) $auditSuccess
                        ));
                    } else {
                        $loadedAces->attach($acl, new FieldEntry(
                            (int) $aceId,
                            $acl,
                            $fieldName,
                            $sids[$key],
                            $grantingStrategy,
                            (int) $mask,
                            (bool) $granting,
                            (bool) $auditFailure,
                            (bool) $auditSuccess
                        ));
                    }
                }
                $ace = $loadedAces->offsetGet($acl);

                // assign ACE to the correct property
                if (null === $objectIdentityId) {
                    if (null === $fieldName) {
                        $aces[$aclId][0][$aceOrder] = $ace;
                    } else {
                        $aces[$aclId][1][$fieldName][$aceOrder] = $ace;
                    }
                } else {
                    if (null === $fieldName) {
                        $aces[$aclId][2][$aceOrder] = $ace;
                    } else {
                        $aces[$aclId][3][$fieldName][$aceOrder] = $ace;
                    }
                }
            }
        }

        // We do not sort on database level since we only want certain subsets to be sorted,
        // and we are going to read the entire result set anyway.
        // Sorting on DB level increases query time by an order of magnitude while it is
        // almost negligible when we use PHPs array sort functions.
        foreach ($aces as $aclId => $aceData) {
            $acl = $acls[$aclId];

            ksort($aceData[0]);
            $aclClassAcesProperty->setValue($acl, $aceData[0]);

            foreach (array_keys($aceData[1]) as $fieldName) {
                ksort($aceData[1][$fieldName]);
            }
            $aclClassFieldAcesProperty->setValue($acl, $aceData[1]);

            ksort($aceData[2]);
            $aclObjectAcesProperty->setValue($acl, $aceData[2]);

            foreach (array_keys($aceData[3]) as $fieldName) {
                ksort($aceData[3][$fieldName]);
            }
            $aclObjectFieldAcesProperty->setValue($acl, $aceData[3]);
        }

        // fill-in parent ACLs where this hasn't been done yet cause the parent ACL was not
        // yet available
        $processed = 0;
        foreach ($parentIdToFill as $acl) {
            $parentId = $parentIdToFill->offsetGet($acl);

            // let's see if we have already hydrated this
            if (isset($acls[$parentId])) {
                $aclParentAclProperty->setValue($acl, $acls[$parentId]);
                ++$processed;

                continue;
            }
        }

        // reset reflection changes
        $aclClassAcesProperty->setAccessible(false);
        $aclClassFieldAcesProperty->setAccessible(false);
        $aclObjectAcesProperty->setAccessible(false);
        $aclObjectFieldAcesProperty->setAccessible(false);
        $aclParentAclProperty->setAccessible(false);

        // this should never be true if the database integrity hasn't been compromised
        if ($processed < count($parentIdToFill)) {
            throw new \RuntimeException('Not all parent ids were populated. This implies an integrity problem.');
        }

        return $result;
    }

    /**
     * Constructs the query used for looking up object identities and associated
     * ACEs, and security identities limited by SIDs.
     *
     * @param array $ancestorIds
     * @param array $sids
     *
     * @return array [sql, param values, param types]
     */
    private function getLookupSqlBySids(array $ancestorIds, array $sids): array
    {
        [$sql, $params, $types] = $this->getLookupSql($ancestorIds);

        if (count($sids)) {
            $sidsArray = [];
            foreach ($sids as $sid) {
                [$identifier] = $this->parseSecurityIdentity($sid);
                $sidsArray[] = $identifier;
            }
            $sql .= ' AND s.identifier in (?)';
            $params[] = $sidsArray;
            $types[] = Connection::PARAM_STR_ARRAY;
        }

        return [$sql, $params, $types];
    }

    private function createNotAllAclsFoundException(\SplObjectStorage $partialResult): NotAllAclsFoundException
    {
        $exception = new NotAllAclsFoundException(
            'The provider could not find ACLs for all object identities.'
        );
        $exception->setPartialResult($partialResult);

        return $exception;
    }

    protected function getOidKey(string $classType, ?string $objectIdentifier): string
    {
        return $objectIdentifier . $classType;
    }

    protected function getSidKey(array $sids): string
    {
        $sidsString = 'sid';
        foreach ($sids as $sid) {
            $sidsString .= $this->sidConverter->convert($sid);
        }

        return $sidsString;
    }

    /**
     * Get Security Identifier and Username flag to create SQL queries
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSecurityIdentity(SecurityIdentityInterface $sid): array
    {
        if ($sid instanceof UserSecurityIdentity) {
            return [$sid->getClass() . '-' . $sid->getUsername(), true];
        }
        if ($sid instanceof RoleSecurityIdentity) {
            return [$sid->getRole(), false];
        }

        throw new \InvalidArgumentException('Unsupported SID type: %s.' . get_class($sid));
    }

    protected function buildSecurityIdentity(string $securityIdentifier, ?string $username): SecurityIdentityInterface
    {
        if ($username) {
            $pos = strpos($securityIdentifier, '-');

            return new UserSecurityIdentity(
                substr($securityIdentifier, $pos + 1),
                substr($securityIdentifier, 0, $pos)
            );
        }

        return new RoleSecurityIdentity($securityIdentifier);
    }

    /**
     * Creates a new instance of an empty ACL object.
     */
    protected function createEmptyAcl(ObjectIdentityInterface $oid): AclInterface
    {
        return new Acl(self::EMPTY_ACL_ID, $oid, $this->permissionGrantingStrategy, [], false);
    }
}
