<?php

/*
 * This file is a copy of {@see \Symfony\Component\Security\Acl\Dbal\MutableAclProvider}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\SecurityBundle\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\PropertyChangedListener;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Exception\ConcurrentModificationException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * An implementation of the MutableAclProviderInterface using Doctrine DBAL.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MutableAclProvider extends AclProvider implements MutableAclProviderInterface, PropertyChangedListener
{
    /** @var \SplObjectStorage  */
    private $propertyChanges;

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
        parent::__construct($connection, $permissionGrantingStrategy, $options, $cache);
        $this->propertyChanges = new \SplObjectStorage();
    }

    /**
     * Clear cache by $oid
     */
    public function clearOidCache(ObjectIdentityInterface $oid)
    {
        unset($this->notFoundAcls[$this->getOidKey($oid->getType(), $oid->getIdentifier())]);
        $this->cache->evictFromCacheByIdentity($oid);
    }

    /**
     * Puts an empty ACL object into the cache for the given OID.
     *
     * @param ObjectIdentityInterface     $oid
     * @param SecurityIdentityInterface[] $sids
     */
    public function cacheEmptyAcl(ObjectIdentityInterface $oid, array $sids): void
    {
        $this->cache->putInCacheBySids($this->createEmptyAcl($oid), $sids);
    }

    /**
     * Checks whether the given ACL is empty.
     */
    public function isEmptyAcl(AclInterface $acl): bool
    {
        return method_exists($acl, 'getId') && $acl->getId() === self::EMPTY_ACL_ID;
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
        [$sql, $params, $types] = $this->getUpdateSecurityIdentitySql($sid, $oldName);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * Deletes all ACL including class data for a given object identity.
     */
    public function deleteAclClass(ObjectIdentityInterface $oid)
    {
        $this->connection->beginTransaction();
        try {
            $this->deleteAcl($oid);
            [$sql, $params, $types] = $this->getDeleteClassIdSql($oid->getType());
            $this->connection->executeStatement($sql, $params, $types);

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
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getUpdateSecurityIdentitySql(SecurityIdentityInterface $sid, $oldName)
    {
        if ($sid instanceof UserSecurityIdentity) {
            return $this->getUpdateUserSecurityIdentitySql($sid, $oldName);
        }

        if ($sid instanceof RoleSecurityIdentity) {
            if ($sid->getRole() == $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $oldName;
            $newIdentifier = $sid->getRole();
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity.'
            );
        }

        return [
            sprintf(
                'UPDATE %s SET identifier = ? WHERE identifier = ? AND username = ?',
                $this->options['sid_table_name']
            ),
            [$newIdentifier, $oldIdentifier, false],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::BOOLEAN]
        ];
    }

    /**
     * Constructs the SQL to delete an ACL class.
     *
     * @param string $classType
     * @return array [sql, param values, param types]
     */
    protected function getDeleteClassIdSql($classType)
    {
        [$sql, $params, $types] = $this->getSelectClassIdSql($classType);

        return [preg_replace('/^SELECT id FROM/', 'DELETE FROM', $sql), $params, $types];
    }

    /**
     * {@inheritdoc}
     */
    public function createAcl(ObjectIdentityInterface $oid)
    {
        if (false !== $this->retrieveObjectIdentityPrimaryKey($oid)) {
            $objectName = method_exists($oid, '__toString') ? $oid : get_class($oid);
            throw new AclAlreadyExistsException(sprintf('%s is already associated with an ACL.', $objectName));
        }

        $this->connection->beginTransaction();
        try {
            $this->createObjectIdentity($oid);

            $pk = $this->retrieveObjectIdentityPrimaryKey($oid);
            [$sql, $params, $types] = $this->getInsertObjectIdentityRelationSql($pk, $pk);
            $this->connection->executeStatement($sql, $params, $types);

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

        // re-read the ACL from the database to ensure proper caching, etc.
        unset($this->notFoundAcls[$this->getOidKey($oid->getType(), $oid->getIdentifier())]);
        return $this->findAcl($oid);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAcl(ObjectIdentityInterface $oid)
    {
        $this->connection->beginTransaction();
        try {
            foreach ($this->findChildren($oid, true) as $childOid) {
                $this->deleteAcl($childOid);
            }

            $oidPK = $this->retrieveObjectIdentityPrimaryKey($oid);

            $this->deleteAccessControlEntries($oidPK);
            $this->deleteObjectIdentityRelations($oidPK);
            $this->deleteObjectIdentity($oidPK);

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

        // evict the ACL from the in-memory identity map
        $oidKey = $this->getOidKey($oid->getType(), $oid->getIdentifier());
        unset($this->notFoundAcls[$oidKey]);
        if (isset($this->loadedAcls[$oidKey])) {
            foreach ($this->loadedAcls[$oidKey] as $acl) {
                $this->propertyChanges->offsetUnset($acl);
                foreach ($this->loadedAces as $loadedAces) {
                    if ($loadedAces->contains($acl)) {
                        $loadedAces->offsetUnset($acl);
                    }
                }
            }
            unset($this->loadedAcls[$oidKey]);
        }

        // evict the ACL from any caches
        if (null !== $this->cache) {
            $this->cache->evictFromCacheByIdentity($oid);
        }
    }

    /**
     * Deletes the security identity from the database.
     * ACL entries have the CASCADE option on their foreign key so they will also get deleted.
     *
     * @throws \InvalidArgumentException
     */
    public function deleteSecurityIdentity(SecurityIdentityInterface $sid)
    {
        [$sql, $params, $types] = $this->getDeleteSecurityIdentityIdSql($sid);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function findAcls(array $oids, array $sids = [])
    {
        $result = parent::findAcls($oids, $sids);

        foreach ($result as $oid) {
            $acl = $result->offsetGet($oid);

            if (!$sids && !$this->propertyChanges->contains($acl) && $acl instanceof MutableAclInterface) {
                $acl->addPropertyChangedListener($this);
                $this->propertyChanges->attach($acl, []);
            }

            $parentAcl = $acl->getParentAcl();
            while (null !== $parentAcl) {
                if (!$sids && !$this->propertyChanges->contains($parentAcl) && $acl instanceof MutableAclInterface) {
                    $parentAcl->addPropertyChangedListener($this);
                    $this->propertyChanges->attach($parentAcl, []);
                }

                $parentAcl = $parentAcl->getParentAcl();
            }
        }

        return $result;
    }

    /**
     * Implementation of PropertyChangedListener.
     *
     * This allows us to keep track of which values have been changed, so we don't
     * have to do a full introspection when ->updateAcl() is called.
     *
     * @param mixed  $sender
     * @param string $propertyName
     * @param mixed  $oldValue
     * @param mixed  $newValue
     *
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function propertyChanged($sender, $propertyName, $oldValue, $newValue)
    {
        if (!$sender instanceof MutableAclInterface && !$sender instanceof EntryInterface) {
            throw new \InvalidArgumentException(
                '$sender must be an instance of MutableAclInterface, or EntryInterface.'
            );
        }

        if ($sender instanceof EntryInterface) {
            if (null === $sender->getId()) {
                return;
            }

            $ace = $sender;
            $sender = $ace->getAcl();
        } else {
            $ace = null;
        }

        if (false === $this->propertyChanges->contains($sender)) {
            throw new \InvalidArgumentException('$sender is not being tracked by this provider.');
        }

        $propertyChanges = $this->propertyChanges->offsetGet($sender);
        if (null === $ace) {
            if (isset($propertyChanges[$propertyName])) {
                $oldValue = $propertyChanges[$propertyName][0];
                if ($oldValue === $newValue) {
                    unset($propertyChanges[$propertyName]);
                } else {
                    $propertyChanges[$propertyName] = [$oldValue, $newValue];
                }
            } else {
                $propertyChanges[$propertyName] = [$oldValue, $newValue];
            }
        } else {
            if (!isset($propertyChanges['aces'])) {
                $propertyChanges['aces'] = new \SplObjectStorage();
            }

            $acePropertyChanges = $propertyChanges['aces']->contains($ace)
                ? $propertyChanges['aces']->offsetGet($ace)
                : [];

            if (isset($acePropertyChanges[$propertyName])) {
                $oldValue = $acePropertyChanges[$propertyName][0];
                if ($oldValue === $newValue) {
                    unset($acePropertyChanges[$propertyName]);
                } else {
                    $acePropertyChanges[$propertyName] = [$oldValue, $newValue];
                }
            } else {
                $acePropertyChanges[$propertyName] = [$oldValue, $newValue];
            }

            if (count($acePropertyChanges) > 0) {
                $propertyChanges['aces']->offsetSet($ace, $acePropertyChanges);
            } else {
                $propertyChanges['aces']->offsetUnset($ace);

                if (0 === count($propertyChanges['aces'])) {
                    unset($propertyChanges['aces']);
                }
            }
        }

        $this->propertyChanges->offsetSet($sender, $propertyChanges);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function updateAcl(MutableAclInterface $acl)
    {
        if (!$this->propertyChanges->contains($acl)) {
            throw new \InvalidArgumentException('$acl is not tracked by this provider.');
        }

        $propertyChanges = $this->propertyChanges->offsetGet($acl);
        // check if any changes were made to this ACL
        if (0 === count($propertyChanges)) {
            return;
        }

        $sets = $sharedPropertyChanges = [];

        $this->connection->beginTransaction();
        try {
            if (isset($propertyChanges['entriesInheriting'])) {
                $sets[] = 'entries_inheriting = '
                    . $this->connection->getDatabasePlatform()->convertBooleans(
                        $propertyChanges['entriesInheriting'][1]
                    );
            }

            if (isset($propertyChanges['parentAcl'])) {
                if (null === $propertyChanges['parentAcl'][1]) {
                    $sets[] = 'parent_object_identity_id = NULL';
                } else {
                    $sets[] = 'parent_object_identity_id = '.(int) $propertyChanges['parentAcl'][1]->getId();
                }

                $this->regenerateAncestorRelations($acl);
                $childAcls = $this->findAcls($this->findChildren($acl->getObjectIdentity(), false));
                foreach ($childAcls as $childOid) {
                    $this->regenerateAncestorRelations($childAcls[$childOid]);
                }
            }

            // check properties for deleted, and created ACEs, and perform deletions
            // we need to perform deletions before updating existing ACEs, in order to
            // preserve uniqueness of the order field
            if (isset($propertyChanges['classAces'])) {
                $this->updateOldAceProperty('classAces', $propertyChanges['classAces']);
            }
            if (isset($propertyChanges['classFieldAces'])) {
                $this->updateOldFieldAceProperty('classFieldAces', $propertyChanges['classFieldAces']);
            }
            if (isset($propertyChanges['objectAces'])) {
                $this->updateOldAceProperty('objectAces', $propertyChanges['objectAces']);
            }
            if (isset($propertyChanges['objectFieldAces'])) {
                $this->updateOldFieldAceProperty('objectFieldAces', $propertyChanges['objectFieldAces']);
            }

            // this includes only updates of existing ACEs, but neither the creation, nor
            // the deletion of ACEs; these are tracked by changes to the ACL's respective
            // properties (classAces, classFieldAces, objectAces, objectFieldAces)
            if (isset($propertyChanges['aces'])) {
                $this->updateAces($propertyChanges['aces']);
            }

            // check properties for deleted, and created ACEs, and perform creations
            if (isset($propertyChanges['classAces'])) {
                $this->updateNewAceProperty('classAces', $propertyChanges['classAces']);
                $sharedPropertyChanges['classAces'] = $propertyChanges['classAces'];
            }
            if (isset($propertyChanges['classFieldAces'])) {
                $this->updateNewFieldAceProperty('classFieldAces', $propertyChanges['classFieldAces']);
                $sharedPropertyChanges['classFieldAces'] = $propertyChanges['classFieldAces'];
            }
            if (isset($propertyChanges['objectAces'])) {
                $this->updateNewAceProperty('objectAces', $propertyChanges['objectAces']);
            }
            if (isset($propertyChanges['objectFieldAces'])) {
                $this->updateNewFieldAceProperty('objectFieldAces', $propertyChanges['objectFieldAces']);
            }

            $oidKey = $this->getOidKey(
                $acl->getObjectIdentity()->getType(),
                $acl->getObjectIdentity()->getIdentifier()
            );
            unset($this->notFoundAcls[$oidKey]);
            $emptySidKey = $this->getSidKey([]);
            foreach ($this->loadedAcls[$oidKey] as $sidKey => $sameTypeAcl) {
                if ($sidKey !== $emptySidKey) {
                    foreach ($this->loadedAces as $loadedAces) {
                        if ($loadedAces->contains($sameTypeAcl)) {
                            $loadedAces->offsetUnset($sameTypeAcl);
                        }
                    }
                    unset($this->loadedAcls[$oidKey][$sidKey]);
                }
            }

            // if there have been changes to shared properties, we need to synchronize other
            // ACL instances for object identities of the same type that are already in-memory
            if (count($sharedPropertyChanges) > 0) {
                $classAcesProperty = new \ReflectionProperty(Acl::class, 'classAces');
                $classAcesProperty->setAccessible(true);
                $classFieldAcesProperty = new \ReflectionProperty(Acl::class, 'classFieldAces');
                $classFieldAcesProperty->setAccessible(true);

                foreach ($this->loadedAcls[$oidKey] as $sidKey => $sameTypeAcl) {
                    if (isset($sharedPropertyChanges['classAces'])) {
                        if ($acl !== $sameTypeAcl
                            && $classAcesProperty->getValue($sameTypeAcl) !== $sharedPropertyChanges['classAces'][0]
                        ) {
                            throw new ConcurrentModificationException(
                                'The "classAces" property has been modified concurrently.'
                            );
                        }

                        $classAcesProperty->setValue($sameTypeAcl, $sharedPropertyChanges['classAces'][1]);
                    }

                    if (isset($sharedPropertyChanges['classFieldAces'])) {
                        if ($acl !== $sameTypeAcl
                            && $classFieldAcesProperty->getValue($sameTypeAcl)
                                !== $sharedPropertyChanges['classFieldAces'][0]
                        ) {
                            throw new ConcurrentModificationException(
                                'The "classFieldAces" property has been modified concurrently.'
                            );
                        }

                        $classFieldAcesProperty->setValue($sameTypeAcl, $sharedPropertyChanges['classFieldAces'][1]);
                    }
                }
            }

            // persist any changes to the acl_object_identities table
            if (count($sets) > 0) {
                [$sql, $params, $types] = $this->getUpdateObjectIdentitySql($acl->getId(), $sets);
                $this->connection->executeStatement($sql, $params, $types);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

        $this->propertyChanges->offsetSet($acl, []);

        if (null !== $this->cache) {
            if (count($sharedPropertyChanges) > 0) {
                // FIXME: Currently, there is no easy way to clear the cache for ACLs
                //        of a certain type. The problem here is that we need to make
                //        sure to clear the cache of all child ACLs as well, and these
                //        child ACLs might be of a different class type.
                $this->cache->clearCache();
            } else {
                // if there are no shared property changes, it's sufficient to just delete
                // the cache for this ACL
                $this->cache->evictFromCacheByIdentity($acl->getObjectIdentity());

                foreach ($this->findChildren($acl->getObjectIdentity()) as $childOid) {
                    $this->cache->evictFromCacheByIdentity($childOid);
                }
            }
        }
    }

    /**
     * Updates a user security identity when the user's username changes.
     *
     * @param UserSecurityIdentity $usid
     * @param string               $oldUsername
     */
    public function updateUserSecurityIdentity(UserSecurityIdentity $usid, $oldUsername)
    {
        [$sql, $params, $types] = $this->getUpdateUserSecurityIdentitySql($usid, $oldUsername);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * Constructs the SQL for deleting access control entries.
     *
     * @param int $oidPK
     *
     * @return array [sql, param values, param types]
     */
    protected function getDeleteAccessControlEntriesSql($oidPK)
    {
        return [
            sprintf(
                'DELETE FROM %s WHERE object_identity_id = ?',
                $this->options['entry_table_name']
            ),
            [$oidPK],
            [ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for deleting a specific ACE.
     *
     * @param int $acePK
     *
     * @return array [sql, param values, param types]
     */
    protected function getDeleteAccessControlEntrySql($acePK)
    {
        return [
            sprintf(
                'DELETE FROM %s WHERE id = ?',
                $this->options['entry_table_name']
            ),
            [$acePK],
            [ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for deleting an object identity.
     *
     * @param int $pk
     *
     * @return array [sql, param values, param types]
     */
    protected function getDeleteObjectIdentitySql($pk)
    {
        return [
            sprintf(
                'DELETE FROM %s WHERE id = ?',
                $this->options['oid_table_name']
            ),
            [$pk],
            [ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for deleting relation entries.
     *
     * @param int $pk
     *
     * @return array [sql, param values, param types]
     */
    protected function getDeleteObjectIdentityRelationsSql($pk)
    {
        return [
            sprintf(
                'DELETE FROM %s WHERE object_identity_id = ?',
                $this->options['oid_ancestors_table_name']
            ),
            [$pk],
            [ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for inserting an ACE.
     *
     * @param int         $classId
     * @param int|null    $objectIdentityId
     * @param string|null $field
     * @param int         $aceOrder
     * @param int         $securityIdentityId
     * @param string      $strategy
     * @param int         $mask
     * @param bool        $granting
     * @param bool        $auditSuccess
     * @param bool        $auditFailure
     *
     * @return array [sql, param values, param types]
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        return [
            sprintf(
                'INSERT INTO %s (class_id, object_identity_id, field_name, ace_order, security_identity_id, mask,'
                . ' granting, granting_strategy, audit_success, audit_failure) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $this->options['entry_table_name']
            ),
            [
                $classId,
                null === $objectIdentityId ? null : (int) $objectIdentityId,
                $field,
                $aceOrder,
                $securityIdentityId,
                $mask,
                $granting,
                $strategy,
                $auditSuccess,
                $auditFailure
            ],
            [
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::BOOLEAN,
                ParameterType::STRING,
                ParameterType::BOOLEAN,
                ParameterType::BOOLEAN
            ]
        ];
    }

    /**
     * Constructs the SQL for inserting a new class type.
     *
     * @param string $classType
     *
     * @return array [sql, param values, param types]
     */
    protected function getInsertClassSql($classType)
    {
        return [
            sprintf(
                'INSERT INTO %s (class_type) VALUES (?)',
                $this->options['class_table_name']
            ),
            [$classType],
            [ParameterType::STRING]
        ];
    }

    /**
     * Constructs the SQL for inserting a relation entry.
     *
     * @param int $objectIdentityId
     * @param int $ancestorId
     *
     * @return array [sql, param values, param types]
     */
    protected function getInsertObjectIdentityRelationSql($objectIdentityId, $ancestorId)
    {
        return [
            sprintf(
                'INSERT INTO %s (object_identity_id, ancestor_id) VALUES (?, ?)',
                $this->options['oid_ancestors_table_name']
            ),
            [$objectIdentityId, $ancestorId],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for inserting an object identity.
     *
     * @param string $identifier
     * @param int    $classId
     * @param bool   $entriesInheriting
     *
     * @return array [sql, param values, param types]
     */
    protected function getInsertObjectIdentitySql($identifier, $classId, $entriesInheriting)
    {
        return [
            sprintf(
                'INSERT INTO %s (class_id, object_identifier, entries_inheriting) VALUES (?, ?, ?)',
                $this->options['oid_table_name']
            ),
            [$classId, $identifier, $entriesInheriting],
            [ParameterType::INTEGER, ParameterType::STRING, ParameterType::BOOLEAN]
        ];
    }

    /**
     * Constructs the SQL for inserting a security identity.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getInsertSecurityIdentitySql(SecurityIdentityInterface $sid)
    {
        [$identifier, $username] = $this->parseSecurityIdentity($sid);

        return [
            sprintf(
                'INSERT INTO %s (identifier, username) VALUES (?, ?)',
                $this->options['sid_table_name']
            ),
            [$identifier, $username],
            [ParameterType::STRING, ParameterType::BOOLEAN]
        ];
    }

    /**
     * Constructs the SQL for selecting an ACE.
     *
     * @param int    $classId
     * @param int    $oid
     * @param string $field
     * @param int    $order
     *
     * @return array [sql, param values, param types]
     */
    protected function getSelectAccessControlEntryIdSql($classId, $oid, $field, $order)
    {
        $parameters = [$classId, $order];
        $parametersTypes = [ParameterType::INTEGER, ParameterType::INTEGER];

        if (null === $oid) {
            $oidExpression = $this->connection->getDatabasePlatform()->getIsNullExpression('object_identity_id');
        } else {
            $oidExpression = 'object_identity_id = ?';
            $parameters[] = $oid;
            $parametersTypes[] = ParameterType::INTEGER;
        }

        if (null === $field) {
            $fieldExpression = $this->connection->getDatabasePlatform()->getIsNullExpression('field_name');
        } else {
            $fieldExpression = 'field_name = ?';
            $parameters[] = $field;
            $parametersTypes[] = ParameterType::STRING;
        }

        return [
            sprintf(
                'SELECT id FROM %s WHERE class_id = ? AND ace_order = ? AND %s AND %s',
                $this->options['entry_table_name'],
                $oidExpression,
                $fieldExpression
            ),
            $parameters,
            $parametersTypes
        ];
    }

    /**
     * Constructs the SQL for selecting the primary key associated with
     * the passed class type.
     *
     * @param string $classType
     *
     * @return array [sql, param values, param types]
     */
    protected function getSelectClassIdSql($classType)
    {
        return [
            sprintf('SELECT id FROM %s WHERE class_type = ?', $this->options['class_table_name']),
            [$classType],
            [ParameterType::STRING]
        ];
    }

    /**
     * Constructs the SQL for selecting the primary key of a security identity.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getSelectSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        [$identifier, $username] = $this->parseSecurityIdentity($sid);

        return [
            sprintf('SELECT id FROM %s WHERE identifier = ? AND username = ?', $this->options['sid_table_name']),
            [$identifier, $username],
            [ParameterType::STRING, ParameterType::BOOLEAN]
        ];
    }

    /**
     * Constructs the SQL to delete a security identity.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getDeleteSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        [$sql, $params, $types] = $this->getSelectSecurityIdentityIdSql($sid);

        return [preg_replace('/^SELECT id FROM/', 'DELETE FROM', $sql), $params, $types];
    }

    /**
     * Constructs the SQL for updating an object identity.
     *
     * @param int   $pk
     * @param array $changes
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getUpdateObjectIdentitySql($pk, array $changes)
    {
        if (0 === count($changes)) {
            throw new \InvalidArgumentException('There are no changes.');
        }

        return [
            sprintf(
                'UPDATE %s SET %s WHERE id = ?',
                $this->options['oid_table_name'],
                implode(', ', $changes)
            ),
            [$pk],
            [ParameterType::INTEGER]
        ];
    }

    /**
     * Constructs the SQL for updating a user security identity.
     *
     * @param UserSecurityIdentity $usid
     * @param string               $oldUsername
     *
     * @return array [sql, param values, param types]
     */
    protected function getUpdateUserSecurityIdentitySql(UserSecurityIdentity $usid, $oldUsername)
    {
        if ($usid->getUsername() == $oldUsername) {
            throw new \InvalidArgumentException('There are no changes.');
        }

        $oldIdentifier = $usid->getClass().'-'.$oldUsername;
        $newIdentifier = $usid->getClass().'-'.$usid->getUsername();

        return [
            sprintf(
                'UPDATE %s SET identifier = ? WHERE identifier = ? AND username = ?',
                $this->options['sid_table_name']
            ),
            [$newIdentifier, $oldIdentifier, true],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::BOOLEAN]
        ];
    }

    /**
     * Constructs the SQL for updating an ACE.
     *
     * @param int   $pk
     * @param array $sets
     *
     * @return array [sql, param values, param types]
     *
     * @throws \InvalidArgumentException
     */
    protected function getUpdateAccessControlEntrySql($pk, array $sets)
    {
        if (0 === count($sets)) {
            throw new \InvalidArgumentException('There are no changes.');
        }

        $setsSql = '';
        $params = [];
        $types = [];
        foreach ($sets as [$name, $value, $type]) {
            if ($setsSql) {
                $setsSql .= ', ';
            }
            $setsSql .= $name . ' = ?';
            $params[] = $value;
            $types[] = $type;
        }
        $params[] = $pk;
        $types[] = ParameterType::INTEGER;

        return [
            sprintf(
                'UPDATE %s SET %s WHERE id = ?',
                $this->options['entry_table_name'],
                $setsSql
            ),
            $params,
            $types
        ];
    }

    /**
     * Creates the ACL for the passed object identity.
     */
    private function createObjectIdentity(ObjectIdentityInterface $oid)
    {
        $classId = $this->createOrRetrieveClassId($oid->getType());

        [$sql, $params, $types] = $this->getInsertObjectIdentitySql($oid->getIdentifier(), $classId, true);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * Returns the primary key for the passed class type.
     *
     * If the type does not yet exist in the database, it will be created.
     *
     * @param string $classType
     *
     * @return int
     */
    private function createOrRetrieveClassId($classType)
    {
        [$sql, $params, $types] = $this->getSelectClassIdSql($classType);
        if (false !== $id = $this->connection->executeQuery($sql, $params, $types)->fetchColumn()) {
            return $id;
        }

        [$insertSql, $insertParams, $insertTypes] = $this->getInsertClassSql($classType);
        $this->connection->executeStatement($insertSql, $insertParams, $insertTypes);

        return $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * Returns the primary key for the passed security identity.
     *
     * If the security identity does not yet exist in the database, it will be
     * created.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return int
     */
    private function createOrRetrieveSecurityIdentityId(SecurityIdentityInterface $sid)
    {
        [$sql, $params, $types] = $this->getSelectSecurityIdentityIdSql($sid);
        $id = $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
        if (false !== $id) {
            return $id;
        }

        [$insertSql, $insertParams, $insertTypes] = $this->getInsertSecurityIdentitySql($sid);
        $this->connection->executeStatement($insertSql, $insertParams, $insertTypes);

        return $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * Deletes all ACEs for the given object identity primary key.
     *
     * @param int $oidPK
     */
    private function deleteAccessControlEntries($oidPK)
    {
        [$sql, $params, $types] = $this->getDeleteAccessControlEntriesSql($oidPK);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * Deletes the object identity from the database.
     *
     * @param int $pk
     */
    private function deleteObjectIdentity($pk)
    {
        [$sql, $params, $types] = $this->getDeleteObjectIdentitySql($pk);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * Deletes all entries from the relations table from the database.
     *
     * @param int $pk
     */
    private function deleteObjectIdentityRelations($pk)
    {
        [$sql, $params, $types] = $this->getDeleteObjectIdentityRelationsSql($pk);
        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * This regenerates the ancestor table which is used for fast read access.
     */
    private function regenerateAncestorRelations(AclInterface $acl)
    {
        $pk = $acl->getId();
        [$sql, $params, $types] = $this->getDeleteObjectIdentityRelationsSql($pk);
        $this->connection->executeStatement($sql, $params, $types);
        [$sql, $params, $types] = $this->getInsertObjectIdentityRelationSql($pk, $pk);
        $this->connection->executeStatement($sql, $params, $types);

        $parentAcl = $acl->getParentAcl();
        while (null !== $parentAcl) {
            [$sql, $params, $types] = $this->getInsertObjectIdentityRelationSql($pk, $parentAcl->getId());
            $this->connection->executeStatement($sql, $params, $types);

            $parentAcl = $parentAcl->getParentAcl();
        }
    }

    /**
     * This processes new entries changes on an ACE related property (classFieldAces, or objectFieldAces).
     *
     * @param string $name
     * @param array  $changes
     */
    private function updateNewFieldAceProperty($name, array $changes)
    {
        $sids = new \SplObjectStorage();
        $classIds = new \SplObjectStorage();
        foreach ($changes[1] as $field => $new) {
            foreach ($new as $aceOrder => $newEntry) {
                $ace = $newEntry;

                if (null === $ace->getId()) {
                    if ($sids->contains($ace->getSecurityIdentity())) {
                        $sid = $sids->offsetGet($ace->getSecurityIdentity());
                    } else {
                        $sid = $this->createOrRetrieveSecurityIdentityId($ace->getSecurityIdentity());
                    }

                    $oid = $ace->getAcl()->getObjectIdentity();
                    if ($classIds->contains($oid)) {
                        $classId = $classIds->offsetGet($oid);
                    } else {
                        $classId = $this->createOrRetrieveClassId($oid->getType());
                    }

                    $objectIdentityId = $name === 'classFieldAces' ? null : $ace->getAcl()->getId();

                    [$sql, $params, $types] = $this->getInsertAccessControlEntrySql(
                        $classId,
                        $objectIdentityId,
                        $field,
                        $aceOrder,
                        $sid,
                        $ace->getStrategy(),
                        $ace->getMask(),
                        $ace->isGranting(),
                        $ace->isAuditSuccess(),
                        $ace->isAuditFailure()
                    );
                    $this->connection->executeStatement($sql, $params, $types);

                    [$sql, $params, $types] = $this->getSelectAccessControlEntryIdSql(
                        $classId,
                        $objectIdentityId,
                        $field,
                        $aceOrder
                    );
                    $aceId = $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
                    $this->loadedAces[$aceId] = new \SplObjectStorage();
                    $this->loadedAces[$aceId]->attach($ace->getAcl(), $ace);

                    $aceIdProperty = new \ReflectionProperty(Entry::class, 'id');
                    $aceIdProperty->setAccessible(true);
                    $aceIdProperty->setValue($ace, (int) $aceId);
                }
            }
        }
    }

    /**
     * This processes old entries changes on an ACE related property (classFieldAces, or objectFieldAces).
     *
     * @param string $name
     * @param array  $changes
     */
    private function updateOldFieldAceProperty($name, array $changes)
    {
        $currentIds = [];
        foreach ($changes[1] as $field => $new) {
            foreach ($new as $ace) {
                if (null !== $ace->getId()) {
                    $currentIds[$ace->getId()] = true;
                }
            }
        }

        foreach ($changes[0] as $old) {
            foreach ($old as $ace) {
                if (!isset($currentIds[$ace->getId()])) {
                    [$sql, $params, $types] = $this->getDeleteAccessControlEntrySql($ace->getId());
                    $this->connection->executeStatement($sql, $params, $types);
                    unset($this->loadedAces[$ace->getId()]);
                }
            }
        }
    }

    /**
     * This processes new entries changes on an ACE related property (classAces, or objectAces).
     *
     * @param string $name
     * @param array  $changes
     */
    private function updateNewAceProperty($name, array $changes)
    {
        [$old, $new] = $changes;

        $sids = new \SplObjectStorage();
        $classIds = new \SplObjectStorage();
        for ($i = 0, $c = count($new); $i < $c; ++$i) {
            $ace = $new[$i];

            if (null === $ace->getId()) {
                if ($sids->contains($ace->getSecurityIdentity())) {
                    $sid = $sids->offsetGet($ace->getSecurityIdentity());
                } else {
                    $sid = $this->createOrRetrieveSecurityIdentityId($ace->getSecurityIdentity());
                }

                $oid = $ace->getAcl()->getObjectIdentity();
                if ($classIds->contains($oid)) {
                    $classId = $classIds->offsetGet($oid);
                } else {
                    $classId = $this->createOrRetrieveClassId($oid->getType());
                }

                $objectIdentityId = $name === 'classAces' ? null : $ace->getAcl()->getId();

                [$sql, $params, $types] =  $this->getInsertAccessControlEntrySql(
                    $classId,
                    $objectIdentityId,
                    null,
                    $i,
                    $sid,
                    $ace->getStrategy(),
                    $ace->getMask(),
                    $ace->isGranting(),
                    $ace->isAuditSuccess(),
                    $ace->isAuditFailure()
                );
                $this->connection->executeStatement($sql, $params, $types);

                [$sql, $params, $types] = $this->getSelectAccessControlEntryIdSql(
                    $classId,
                    $objectIdentityId,
                    null,
                    $i
                );
                $aceId = $this->connection->executeQuery($sql, $params, $types)->fetchColumn();
                $this->loadedAces[$aceId] = new \SplObjectStorage();
                $this->loadedAces[$aceId]->attach($ace->getAcl(), $ace);

                $aceIdProperty = new \ReflectionProperty($ace, 'id');
                $aceIdProperty->setAccessible(true);
                $aceIdProperty->setValue($ace, (int) $aceId);
            }
        }
    }

    /**
     * This processes old entries changes on an ACE related property (classAces, or objectAces).
     *
     * @param string $name
     * @param array  $changes
     */
    private function updateOldAceProperty($name, array $changes)
    {
        [$old, $new] = $changes;
        $currentIds = [];

        foreach ($new as $ace) {
            if (null !== $ace->getId()) {
                $currentIds[$ace->getId()] = true;
            }
        }

        foreach ($old as $ace) {
            if (!isset($currentIds[$ace->getId()])) {
                [$sql, $params, $types] = $this->getDeleteAccessControlEntrySql($ace->getId());
                $this->connection->executeStatement($sql, $params, $types);
                unset($this->loadedAces[$ace->getId()]);
            }
        }
    }

    /**
     * Persists the changes which were made to ACEs to the database.
     */
    private function updateAces(\SplObjectStorage $aces)
    {
        foreach ($aces as $ace) {
            $this->updateAce($aces, $ace);
        }
    }

    /**
     * @param \SplObjectStorage $aces
     * @param EntryInterface    $ace
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function updateAce(\SplObjectStorage $aces, $ace)
    {
        $propertyChanges = $aces->offsetGet($ace);
        $sets = [];

        if (isset($propertyChanges['aceOrder'])
            && $propertyChanges['aceOrder'][1] > $propertyChanges['aceOrder'][0]
            && $propertyChanges == $aces->offsetGet($ace)) {
            $aces->next();
            if ($aces->valid()) {
                $this->updateAce($aces, $aces->current());
            }
        }

        if (isset($propertyChanges['mask'])) {
            $sets[] = ['mask', $propertyChanges['mask'][1], ParameterType::INTEGER];
        }
        if (isset($propertyChanges['strategy'])) {
            $sets[] = ['granting_strategy', $propertyChanges['strategy'][1], ParameterType::STRING];
        }
        if (isset($propertyChanges['aceOrder'])) {
            $sets[] = ['ace_order', $propertyChanges['aceOrder'][1], ParameterType::INTEGER];
        }
        if (isset($propertyChanges['auditSuccess'])) {
            $sets[] = ['audit_success', $propertyChanges['auditSuccess'][1], ParameterType::BOOLEAN];
        }
        if (isset($propertyChanges['auditFailure'])) {
            $sets[] = ['audit_failure', $propertyChanges['auditFailure'][1], ParameterType::BOOLEAN];
        }

        [$sql, $params, $types] = $this->getUpdateAccessControlEntrySql($ace->getId(), $sets);
        $this->connection->executeStatement($sql, $params, $types);
    }
}
