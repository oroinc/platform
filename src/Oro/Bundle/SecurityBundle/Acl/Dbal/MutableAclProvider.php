<?php

namespace Oro\Bundle\SecurityBundle\Acl\Dbal;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider as BaseMutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * This class extends the Symfony's MutableAclProvider
 * to add additional features required for Oro Platform.
 */
class MutableAclProvider extends BaseMutableAclProvider
{
    /** @var PermissionGrantingStrategyInterface */
    protected $permissionStrategy;

    /**
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
        $this->cache->putInCache(new Acl(0, $oid, $this->permissionStrategy, [], false));
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

        return sprintf(
            'UPDATE %s SET identifier = %s WHERE identifier = %s AND username = %s',
            $this->options['sid_table_name'],
            $this->connection->quote($newIdentifier),
            $this->connection->quote($oldIdentifier),
            $this->connection->getDatabasePlatform()->convertBooleans(false)
        );
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
}
