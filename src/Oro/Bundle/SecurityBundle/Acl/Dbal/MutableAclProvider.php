<?php

namespace Oro\Bundle\SecurityBundle\Acl\Dbal;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider as BaseMutableAclProvider;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;

/**
 * This class extends the standard Symfony MutableAclProvider.
 *
 * @todo Periodically check if updateSecurityIdentity and deleteSecurityIdentity methods exist
 *       in the standard Symfony MutableAclProvider and delete them from this class if so.
 *       Before deleting carefully check standard implementation of these methods,
 *       especially updateSecurityIdentity.
 * @see https://github.com/symfony/symfony/pull/8305
 * @see https://github.com/symfony/symfony/pull/8650
 */
class MutableAclProvider extends BaseMutableAclProvider
{
    /**
     * @var PermissionGrantingStrategyInterface
     */
    protected $permissionStrategy;

    /**
     * @var MutableAclInterface
     */
    protected $updatedAcl;

    /**
     * Constructor.
     *
     * @param Connection                          $connection
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array                               $options
     * @param AclCacheInterface                   $cache
     */
    public function __construct(
        Connection $connection,
        PermissionGrantingStrategyInterface $permissionGrantingStrategy,
        array $options,
        AclCacheInterface $cache = null
    ) {
        $this->permissionStrategy = $permissionGrantingStrategy;
        parent::__construct($connection, $permissionGrantingStrategy, $options, $cache);

    }

    /**
     * Clear cache by $oid
     *
     * @param ObjectIdentityInterface $oid
     */
    public function clearOidCache(ObjectIdentityInterface $oid)
    {
        $this->cache->evictFromCacheByIdentity($oid);
    }

    /**
     * Put in cache empty ACL object for the given OID
     *
     * @param ObjectIdentityInterface $oid
     */
    public function cacheEmptyAcl(ObjectIdentityInterface $oid)
    {
        $this->cache->putInCache(new Acl(0, $oid, $this->permissionStrategy, array(), false));
    }

    /**
     * Checks whether the given ACL is empty
     *
     * @param AclInterface $acl
     * @return bool
     */
    public function isEmptyAcl(AclInterface $acl)
    {
        return method_exists($acl, 'getId') && $acl->getId() === 0;
    }

    /**
     * Put in cache empty ACL object for the given OID indicates that we should use
     * underlying ACL instead it
     *
     * @param ObjectIdentityInterface $oid
     */
    public function cacheWithUnderlyingAcl(ObjectIdentityInterface $oid)
    {
        $this->cache->putInCache(new Acl(-1, $oid, $this->permissionStrategy, array(), false));
    }

    /**
     * Checks whether the given ACL should be replaced with underlying ACL
     *
     * @param AclInterface $acl
     * @return bool
     */
    public function isReplaceWithUnderlyingAcl(AclInterface $acl)
    {
        return method_exists($acl, 'getId') && $acl->getId() === -1;
    }

    /**
     * Initiates a transaction
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commits a transaction
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Rolls back a transaction
     */
    public function rollBack()
    {
        $this->connection->rollBack();
    }

    /**
     * Updates a security identity when the user's username or the role name changes
     *
     * @param SecurityIdentityInterface $sid
     * @param string $oldName The old security identity name.
     *                        It is the user's username if $sid is UserSecurityIdentity
     *                        or the role name if $sid is RoleSecurityIdentity
     */
    public function updateSecurityIdentity(SecurityIdentityInterface $sid, $oldName)
    {
        $this->connection->executeQuery($this->getUpdateSecurityIdentitySql($sid, $oldName));
    }

    /**
     * Deletes the security identity from the database.
     * ACL entries have the CASCADE option on their foreign key so they will also get deleted
     *
     * @param SecurityIdentityInterface $sid
     * @throws \InvalidArgumentException
     */
    public function deleteSecurityIdentity(SecurityIdentityInterface $sid)
    {
        $this->connection->executeQuery($this->getDeleteSecurityIdentityIdSql($sid));
    }

    /**
     * Deletes all ACL including class data for a given object identity.
     *
     * @param ObjectIdentityInterface $oid
     * @throws \Exception
     */
    public function deleteAclClass(ObjectIdentityInterface $oid)
    {
        $this->connection->beginTransaction();
        try {
            $this->deleteAcl($oid);
            $this->connection->executeQuery($this->getDeleteClassIdSql($oid->getType()));

            $this->connection->commit();
        } catch (\Exception $failed) {
            $this->connection->rollBack();

            throw $failed;
        }
    }

    /**
     * Constructs the SQL for updating a security identity.
     *
     * @param SecurityIdentityInterface $sid
     * @param string $oldName
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getUpdateSecurityIdentitySql(SecurityIdentityInterface $sid, $oldName)
    {
        if ($sid instanceof UserSecurityIdentity) {
            if ($sid->getUsername() == $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $sid->getClass() . '-' . $oldName;
            $newIdentifier = $sid->getClass() . '-' . $sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            if ($sid->getRole() == $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $oldName;
            $newIdentifier = $sid->getRole();
            $username = false;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            if ($sid->getId() === $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $sid->getClass() . '-' . $oldName;
            $newIdentifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity' .
                ' or BusinessUnitSecurityIdentity.'
            );
        }

        return sprintf(
            'UPDATE %s SET identifier = %s WHERE identifier = %s AND username = %s',
            $this->options['sid_table_name'],
            $this->connection->quote($newIdentifier),
            $this->connection->quote($oldIdentifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * Constructs the SQL to delete a security identity.
     *
     * @param SecurityIdentityInterface $sid
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getDeleteSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        $select = $this->getSelectSecurityIdentityIdSql($sid);
        $delete = preg_replace('/^SELECT id FROM/', 'DELETE FROM', $select);

        return $delete;
    }

    /**
     * Constructs the SQL to delete an ACL class.
     *
     * @param string $classType
     * @return string
     */
    protected function getDeleteClassIdSql($classType)
    {
        $select = $this->getSelectClassIdSql($classType);
        $delete = preg_replace('/^SELECT id FROM/', 'DELETE FROM', $select);

        return $delete;
    }

    /**
     * Constructs the SQL for inserting a security identity.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getInsertSecurityIdentitySql(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getClass().'-'.$sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            $identifier = $sid->getRole();
            $username = false;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity' .
                ' or BusinessUnitSecurityIdentity.'
            );
        }

        return sprintf(
            'INSERT INTO %s (identifier, username) VALUES (%s, %s)',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * Constructs the SQL for selecting the primary key of a security identity.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getSelectSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getClass().'-'.$sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            $identifier = $sid->getRole();
            $username = false;
        }  elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity' .
                ' or BusinessUnitSecurityIdentity.'
            );
        }

        return sprintf(
            'SELECT id FROM %s WHERE identifier = %s AND username = %s',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateAcl(MutableAclInterface $acl)
    {
        $this->updatedAcl = $acl;
        parent::updateAcl($acl);
        $this->updatedAcl = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInsertAccessControlEntrySql(
        $classId,
        $objectIdentityId,
        $field,
        $aceOrder,
        $securityIdentityId,
        $strategy,
        $mask,
        $granting,
        $auditSuccess,
        $auditFailure
    ) {
        $recordId = $this->updatedAcl && $this->updatedAcl->getObjectIdentity()
            ? $this->updatedAcl->getObjectIdentity()->getIdentifier()
            : null;

        $query = <<<QUERY
            INSERT INTO %s (
                class_id,
                object_identity_id,
                field_name,
                ace_order,
                security_identity_id,
                mask,
                granting,
                granting_strategy,
                audit_success,
                audit_failure,
                record_id
            )
            VALUES (%d, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s)
QUERY;

        return sprintf(
            $query,
            $this->options['entry_table_name'],
            $classId,
            null === $objectIdentityId ? 'NULL' : (int) $objectIdentityId,
            null === $field ? 'NULL' : $this->connection->quote($field),
            $aceOrder,
            $securityIdentityId,
            $mask,
            $this->connection->getDatabasePlatform()->convertBooleans($granting),
            $this->connection->quote($strategy),
            $this->connection->getDatabasePlatform()->convertBooleans($auditSuccess),
            $this->connection->getDatabasePlatform()->convertBooleans($auditFailure),
            null === $recordId ? 'NULL' : (int) $recordId
        );
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
     */
    protected function hydrateObjectIdentities(Statement $stmt, array $oidLookup, array $sids)
    {
        $parentIdToFill = new \SplObjectStorage();
        $acls = $aces = $emptyArray = array();
        $oidCache = $oidLookup;
        $result = new \SplObjectStorage();
        $loadedAces = &$this->loadedAces;
        $loadedAcls = &$this->loadedAcls;
        $permissionGrantingStrategy = $this->permissionStrategy;

        // we need these to set protected properties on hydrated objects
        $aclReflection = new \ReflectionClass('Symfony\Component\Security\Acl\Domain\Acl');
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
            list($aclId,
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
                $securityIdentifier) = $data;

            // has the ACL been hydrated during this hydration cycle?
            if (isset($acls[$aclId])) {
                $acl = $acls[$aclId];
                // has the ACL been hydrated during any previous cycle, or was possibly loaded
                // from cache?
            } elseif (isset($loadedAcls[$classType][$objectIdentifier])) {
                $acl = $loadedAcls[$classType][$objectIdentifier];

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
                $oidLookupKey = $objectIdentifier.$classType;
                if (!isset($oidCache[$oidLookupKey])) {
                    $oidCache[$oidLookupKey] = new ObjectIdentity($objectIdentifier, $classType);
                }

                $acl = new Acl((int) $aclId, $oidCache[$oidLookupKey], $permissionGrantingStrategy, $emptyArray, !!$entriesInheriting);

                // keep a local, and global reference to this ACL
                $loadedAcls[$classType][$objectIdentifier] = $acl;
                $acls[$aclId] = $acl;

                // try to fill in parent ACL, or defer until all ACLs have been hydrated
                if (null !== $parentObjectIdentityId) {
                    if (isset($acls[$parentObjectIdentityId])) {
                        $aclParentAclProperty->setValue($acl, $acls[$parentObjectIdentityId]);
                    } else {
                        $parentIdToFill->attach($acl, $parentObjectIdentityId);
                    }
                }

                $result->attach($oidCache[$oidLookupKey], $acl);
            }

            // check if this row contains an ACE record
            if (null !== $aceId) {
                // have we already hydrated ACEs for this ACL?
                if (!isset($aces[$aclId])) {
                    $aces[$aclId] = array($emptyArray, $emptyArray, $emptyArray, $emptyArray);
                }

                // has this ACE already been hydrated during a previous cycle, or
                // possible been loaded from cache?
                // It is important to only ever have one ACE instance per actual row since
                // some ACEs are shared between ACL instances
                if (!isset($loadedAces[$aceId])) {
                    if (!isset($sids[$key = ($username ? '1' : '0').$securityIdentifier])) {
                        $sids[$key] = $this->getSecurityIdentityFromString($securityIdentifier, $username);

                    }

                    if (null === $fieldName) {
                        $loadedAces[$aceId] = new Entry((int) $aceId, $acl, $sids[$key], $grantingStrategy, (int) $mask, !!$granting, !!$auditFailure, !!$auditSuccess);
                    } else {
                        $loadedAces[$aceId] = new FieldEntry((int) $aceId, $acl, $fieldName, $sids[$key], $grantingStrategy, (int) $mask, !!$granting, !!$auditFailure, !!$auditSuccess);
                    }
                }
                $ace = $loadedAces[$aceId];

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
     * {@inheritdoc}
     */
    public function findAcls(array $oids, array $sids = array())
    {
        $result = new \SplObjectStorage();
        $currentBatch = array();
        $oidLookup = array();

        for ($i = 0, $c = count($oids); $i < $c; ++$i) {
            $oid = $oids[$i];
            $oidLookupKey = $oid->getIdentifier().$oid->getType();
            $oidLookup[$oidLookupKey] = $oid;
            $aclFound = false;

            // check if result already contains an ACL
            if ($result->contains($oid)) {
                $aclFound = true;
            }

            // check if this ACL has already been hydrated
            if (!$aclFound && isset($this->loadedAcls[$oid->getType()][$oid->getIdentifier()])) {
                $acl = $this->loadedAcls[$oid->getType()][$oid->getIdentifier()];

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
                $acl = $this->cache->getFromCacheByIdentity($oid);

                if (null !== $acl) {
                    if ($acl->isSidLoaded($sids)) {
                        // check if any of the parents has been loaded since we need to
                        // ensure that there is only ever one ACL per object identity
                        $parentAcl = $acl->getParentAcl();
                        while (null !== $parentAcl) {
                            $parentOid = $parentAcl->getObjectIdentity();

                            if (isset($this->loadedAcls[$parentOid->getType()][$parentOid->getIdentifier()])) {
                                $acl->setParentAcl($this->loadedAcls[$parentOid->getType()][$parentOid->getIdentifier()]);
                                break;
                            } else {
                                $this->loadedAcls[$parentOid->getType()][$parentOid->getIdentifier()] = $parentAcl;
                                $this->updateAceIdentityMap($parentAcl);
                            }

                            $parentAcl = $parentAcl->getParentAcl();
                        }

                        $this->loadedAcls[$oid->getType()][$oid->getIdentifier()] = $acl;
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
            if (!$aclFound) {
                $currentBatch[] = $oid;
            }

            // Is it time to load the current batch?
            $currentBatchesCount = count($currentBatch);
            if ($currentBatchesCount > 0 && (self::MAX_BATCH_SIZE === $currentBatchesCount || ($i + 1) === $c)) {
                try {
                    $loadedBatch = $this->lookupObjectIdentities($currentBatch, $sids, $oidLookup);
                } catch (AclNotFoundException $aclNotFoundexception) {
                    if ($result->count()) {
                        $partialResultException = new NotAllAclsFoundException('The provider could not find ACLs for all object identities.');
                        $partialResultException->setPartialResult($result);
                        throw $partialResultException;
                    } else {
                        throw $aclNotFoundexception;
                    }
                }
                foreach ($loadedBatch as $loadedOid) {
                    $loadedAcl = $loadedBatch->offsetGet($loadedOid);

                    if (null !== $this->cache) {
                        $this->cache->putInCache($loadedAcl);
                    }

                    if (isset($oidLookup[$loadedOid->getIdentifier().$loadedOid->getType()])) {
                        $result->attach($loadedOid, $loadedAcl);
                    }
                }

                $currentBatch = array();
            }
        }

        // check that we got ACLs for all the identities
        foreach ($oids as $oid) {
            if (!$result->contains($oid)) {
                if (1 === count($oids)) {
                    throw new AclNotFoundException(sprintf('No ACL found for %s.', $oid));
                }

                $partialResultException = new NotAllAclsFoundException('The provider could not find ACLs for all object identities.');
                $partialResultException->setPartialResult($result);

                throw $partialResultException;
            }
        }

        return $result;
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
    protected function lookupObjectIdentities(array $batch, array $sids, array $oidLookup)
    {
        $ancestorIds = $this->getAncestorIds($batch);
        if (!$ancestorIds) {
            throw new AclNotFoundException('There is no ACL for the given object identity.');
        }

        $sql = $this->getLookupSql($ancestorIds);
        $stmt = $this->connection->executeQuery($sql);

        return $this->hydrateObjectIdentities($stmt, $oidLookup, $sids);
    }

    /**
     * Retrieves all the ids which need to be queried from the database
     * including the ids of parent ACLs.
     *
     * @param array $batch
     *
     * @return array
     */
    protected function getAncestorIds(array $batch)
    {
        $sql = $this->getAncestorLookupSql($batch);

        $ancestorIds = array();
        foreach ($this->connection->executeQuery($sql)->fetchAll() as $data) {
            // FIXME: skip ancestors which are cached

            $ancestorIds[] = $data['ancestor_id'];
        }

        return $ancestorIds;
    }

    /**
     * This method is called when an ACL instance is retrieved from the cache.
     *
     * @param AclInterface $acl
     */
    protected function updateAceIdentityMap(AclInterface $acl)
    {
        foreach (array('classAces', 'classFieldAces', 'objectAces', 'objectFieldAces') as $property) {
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
     * Does either overwrite the passed ACE, or saves it in the global identity
     * map to ensure every ACE only gets instantiated once.
     *
     * @param array &$aces
     */
    protected function doUpdateAceIdentityMap(array &$aces)
    {
        foreach ($aces as $index => $ace) {
            if (isset($this->loadedAces[$ace->getId()])) {
                $aces[$index] = $this->loadedAces[$ace->getId()];
            } else {
                $this->loadedAces[$ace->getId()] = $ace;
            }
        }
    }

    /**
     * @param string $securityIdentifier
     * @param string $username
     *
     * @return BusinessUnitSecurityIdentity|RoleSecurityIdentity
     */
    protected function getSecurityIdentityFromString($securityIdentifier, $username)
    {
        if ($username) {
            return new UserSecurityIdentity(
                substr($securityIdentifier, 1 + $pos = strpos($securityIdentifier, '-')),
                substr($securityIdentifier, 0, $pos)
            );
        } else {
            $pos = strpos($securityIdentifier, '-');

            if ($pos !== false) {
                $identifier = substr($securityIdentifier, 1 + $pos);
                $className = substr($securityIdentifier, 0, $pos);
                $sidReflection = new \ReflectionClass($className);
                $interfaceNames = $sidReflection->getInterfaceNames();
                if (in_array(
                    'Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface',
                    (array) $interfaceNames)
                ) {
                    return new BusinessUnitSecurityIdentity($identifier, $className);
                }
            }

            return new RoleSecurityIdentity($securityIdentifier);
        }
    }
}
