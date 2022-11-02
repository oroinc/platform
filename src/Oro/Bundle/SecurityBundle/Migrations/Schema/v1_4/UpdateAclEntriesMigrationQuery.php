<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UpdateAclEntriesMigrationQuery extends ParametrizedSqlMigrationQuery
{
    private const OLD_SERVICE_BITS            = -32768; // 0xFFFF8000
    private const NEW_SERVICE_BITS            = -33554432; // 0xFE000000
    private const OLD_SERVICE_BITS_OFFSET     = 15;
    private const NEW_SERVICE_BITS_OFFSET     = 25;
    private const OLD_MAX_PERMISSIONS_IN_MASK = 3;
    private const NEW_MAX_PERMISSIONS_IN_MASK = 5;
    private const PERMISSION_OFFSET           = 5;
    private const FIRST_PERMISSION_MASK       = 31; // 0x0000001F

    /** @var PermissionConfigurationProvider */
    private $permissionConfigurationProvider;

    /** @var AclCacheInterface */
    private $aclCache;

    /** @var string */
    private $entriesTableName;

    /** @var string */
    private $objectIdentitiesTableName;

    /** @var string */
    private $aclClassesTableName;

    public function __construct(
        PermissionConfigurationProvider $permissionConfigurationProvider,
        AclCacheInterface $aclCache,
        string $entriesTableName,
        string $objectIdentitiesTableName,
        string $aclClassesTableName
    ) {
        parent::__construct();
        $this->permissionConfigurationProvider = $permissionConfigurationProvider;
        $this->aclCache = $aclCache;
        $this->entriesTableName = $entriesTableName;
        $this->objectIdentitiesTableName = $objectIdentitiesTableName;
        $this->aclClassesTableName = $aclClassesTableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $messages = parent::getDescription();

        array_unshift($messages, 'Update all ACE`s to store 5 permissions in one mask instead of 3');

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $rootClassId = $this->getRootClassId($logger);

        $this->prepareQueriesForUpdateAceMask($logger, $rootClassId);
        parent::processQueries($logger, $dryRun);

        if (!$dryRun && $this->queries) {
            $this->queries = [];
            $this->prepareQueriesForUpdateAceOrder($logger);
            parent::processQueries($logger, $dryRun);

            $this->aclCache->clearCache();
        }
    }

    private function prepareQueriesForUpdateAceMask(LoggerInterface $logger, int $rootClassId)
    {
        $aces = $this->loadAces($logger);

        $rootAces = [];
        $groupedAces = [];
        $maxAceOrders = [];
        foreach ($aces as $ace) {
            $classId = $ace['class_id'];
            $sid = $ace['security_identity_id'];
            if ($classId === $rootClassId) {
                $rootAces[$sid][] = $ace;
            } else {
                $key = sprintf('%s-%s-%s', $classId, $sid, $ace['object_identity_id']);
                $groupedAces[$key][] = $ace;
            }
            $aceOrderKey = $this->getAceOrderKey($ace);
            $maxAceOrders[$aceOrderKey] = max($ace['ace_order'], $maxAceOrders[$aceOrderKey] ?? 0);
        }

        if (!$this->isUpdateAceMasksRequired($rootAces)) {
            return;
        }

        $forUpdate = [];
        $forDelete = [];
        $forInsert = [];

        $maxNumberOfPermissions = count($this->permissionConfigurationProvider->getPermissionConfiguration());
        [$rootPermissions, $rootNewMasks] = $this->processRootAces($rootAces, $maxNumberOfPermissions);
        foreach ($rootAces as $sid => $aces) {
            $newMasks = $rootNewMasks[$sid] ?? [];
            $this->addQueries($aces, $newMasks, $forUpdate, $forDelete, $forInsert, $maxAceOrders);
        }
        foreach ($groupedAces as $aces) {
            $sid = $aces[0]['security_identity_id'];
            $newMasks = $this->processGroupAces(
                $aces,
                $rootPermissions[$sid] ?? [],
                $rootNewMasks[$sid] ?? [],
                $maxNumberOfPermissions
            );
            $this->addQueries($aces, $newMasks, $forUpdate, $forDelete, $forInsert, $maxAceOrders);
        }

        $this->updateAces($forUpdate);
        $this->deleteAces($forDelete);
        $this->insertAces($forInsert);
    }

    /**
     * @param array $rootAces
     *
     * @return bool
     */
    private function isUpdateAceMasksRequired(array $rootAces)
    {
        foreach ($rootAces as $sid => $aces) {
            foreach ($aces as $ace) {
                if ($ace['mask'] & self::NEW_SERVICE_BITS) {
                    return false;
                }
            }
        }

        return true;
    }

    private function prepareQueriesForUpdateAceOrder(LoggerInterface $logger)
    {
        $aces = $this->loadAces($logger, 'e.id, e.class_id, e.object_identity_id, e.ace_order', 'e.ace_order');

        $groupedAces = [];
        foreach ($aces as $ace) {
            $groupedAces[$this->getAceOrderKey($ace)][] = [$ace['id'], $ace['ace_order']];
        }

        $forUpdate = [];

        foreach ($groupedAces as $aces) {
            $correctOrder = 0;
            foreach ($aces as [$id, $order]) {
                if ($order !== $correctOrder) {
                    $forUpdate[$id] = $correctOrder;
                }
                $correctOrder++;
            }
        }

        $this->updateAceOrders($forUpdate);
    }

    /**
     * @param array $ace
     *
     * @return string
     */
    private function getAceOrderKey(array $ace)
    {
        return sprintf('%s-%s', $ace['class_id'], $ace['object_identity_id']);
    }

    private function addQueries(
        array $aces,
        array $newMasks,
        array &$forUpdate,
        array &$forDelete,
        array &$forInsert,
        array &$maxAceOrders
    ) {
        $newMaskCount = count($newMasks);
        $aceCount = count($aces);
        for ($i = 0; $i < $newMaskCount; $i++) {
            $newMask = $newMasks[$i];
            if ($i < $aceCount) {
                $forUpdate[] = ['id' => $aces[$i]['id'], 'mask' => $newMask];
            } else {
                $newAce = $aces[0];
                unset($newAce['id']);
                $newAce['mask'] = $newMask;
                $newAce['audit_success'] = false;
                $newAce['audit_failure'] = false;
                $aceOrderKey = $this->getAceOrderKey($newAce);
                $maxAceOrder = $maxAceOrders[$aceOrderKey];
                $maxAceOrder++;
                $maxAceOrders[$aceOrderKey] = $maxAceOrder;
                $newAce['ace_order'] = $maxAceOrder;
                $forInsert[] = $newAce;
            }
        }
        while ($i < $aceCount) {
            $forDelete[] = $aces[$i]['id'];
            $i++;
        }
    }

    /**
     * @param array $rootAces [sid => [ace, ...], ...]
     * @param int   $maxNumberOfPermissions
     *
     * @return array [[sid => [permission, ...], ...], [sid => [service bit index => mask, ...], ...]]
     */
    private function processRootAces(array $rootAces, int $maxNumberOfPermissions)
    {
        $permissions = [];
        $masks = [];
        foreach ($rootAces as $sid => $aces) {
            $permissions[$sid] = $this->getGroupPermissions($aces, [], $maxNumberOfPermissions);
            $masks[$sid] = $this->getNewMasks($permissions[$sid], []);
        }

        return [$permissions, $masks];
    }

    /**
     * @param array $aces
     * @param array $rootPermissions [permission, ...]
     * @param array $rootNewMasks    [service bit index => mask, ...]
     * @param int   $maxNumberOfPermissions
     *
     * @return array [mask, ...]
     */
    private function processGroupAces(
        array $aces,
        array $rootPermissions,
        array $rootNewMasks,
        int $maxNumberOfPermissions
    ) {
        $result = [];
        $permissions = $this->getGroupPermissions($aces, $rootPermissions, $maxNumberOfPermissions);
        $newMasks = $this->getNewMasks($permissions, $rootPermissions);
        foreach ($newMasks as $serviceBitIndex => $mask) {
            if ($mask !== ($rootNewMasks[$serviceBitIndex] ?? 0)) {
                $result[] = $mask;
            }
        }

        return $result;
    }

    /**
     * @param array $aces
     * @param array $rootPermissions [permission, ...]
     * @param int   $maxNumberOfPermissions
     *
     * @return int[]
     */
    private function getGroupPermissions(array $aces, array $rootPermissions, int $maxNumberOfPermissions)
    {
        $permissions = [];
        $lastServiceBitIndex = -1;
        foreach ($aces as $ace) {
            $mask = $ace['mask'];
            $serviceBitIndex = ($mask & self::OLD_SERVICE_BITS) >> self::OLD_SERVICE_BITS_OFFSET;
            for ($j = $lastServiceBitIndex + 1; $j < $serviceBitIndex; $j++) {
                for ($i = 0; $i < self::OLD_MAX_PERMISSIONS_IN_MASK; $i++) {
                    $permissions[] = $rootPermissions[($j * self::OLD_MAX_PERMISSIONS_IN_MASK) + $i] ?? 0;
                }
            }
            for ($i = 0; $i < self::OLD_MAX_PERMISSIONS_IN_MASK; $i++) {
                $permissions[] = $mask & self::FIRST_PERMISSION_MASK;
                $mask >>= self::PERMISSION_OFFSET;
            }
            $lastServiceBitIndex = $serviceBitIndex;
        }
        if (count($permissions) > $maxNumberOfPermissions) {
            $permissions = array_slice($permissions, 0, $maxNumberOfPermissions);
        }

        return $permissions;
    }

    /**
     * @param array $permissions     [permission, ...]
     * @param array $rootPermissions [permission, ...]
     *
     * @return int[] [service bit index => mask, ...]
     */
    private function getNewMasks(array $permissions, array $rootPermissions)
    {
        $serviceBitIndex = 0;
        $newMasks = [];
        $permissionIndex = 0;
        $permissionCount = max(count($permissions), count($rootPermissions));
        while ($permissionIndex < $permissionCount) {
            $newMask = 0;
            for ($i = 0; $i < self::NEW_MAX_PERMISSIONS_IN_MASK; $i++) {
                $permission = $permissions[$permissionIndex] ?? $rootPermissions[$permissionIndex];
                $permissionIndex++;
                $newMask |= ($permission << (self::PERMISSION_OFFSET * $i));
                if ($permissionIndex >= $permissionCount) {
                    break;
                }
            }
            $newMasks[$serviceBitIndex] = ($serviceBitIndex << self::NEW_SERVICE_BITS_OFFSET) | $newMask;
            $serviceBitIndex++;
        }

        return $newMasks;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return int
     */
    private function getRootClassId(LoggerInterface $logger)
    {
        $query = sprintf(
            'SELECT c.id FROM %s AS c WHERE c.class_type = :class_type',
            $this->aclClassesTableName
        );
        $params = ['class_type' => ObjectIdentityFactory::ROOT_IDENTITY_TYPE];
        $types = ['class_type' => Types::STRING];
        $this->logQuery($logger, $query, $params, $types);

        $rows = $this->connection->fetchAll($query, $params, $types);

        return (int)$rows[0]['id'];
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $select
     * @param string          $orderBy
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadAces(
        LoggerInterface $logger,
        string $select = 'e.*',
        $orderBy = 'e.security_identity_id, e.mask'
    ) {
        if ('e.*' === $select) {
            $select = 'e.id, e.class_id, e.object_identity_id, e.security_identity_id,'
                . ' e.ace_order, e.mask, e.granting, e.granting_strategy';
        }
        $query = sprintf(
            'SELECT %s FROM %s AS e'
            . ' INNER JOIN %s AS o ON e.class_id = o.class_id'
            . ' AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL)'
            . ' INNER JOIN %s AS c ON c.id = o.class_id'
            . ' WHERE o.object_identifier = :oid AND e.field_name IS NULL'
            . ' ORDER BY e.class_id, e.object_identity_id, %s',
            $select,
            $this->entriesTableName,
            $this->objectIdentitiesTableName,
            $this->aclClassesTableName,
            $orderBy
        );
        $params = ['oid' => EntityAclExtension::NAME];
        $types = ['oid' => Types::STRING];
        $this->logQuery($logger, $query, $params, $types);

        $rows = $this->connection->fetchAll($query, $params, $types);

        $result = [];
        foreach ($rows as $row) {
            $resultRow = [];
            foreach ($row as $name => $value) {
                switch ($name) {
                    case 'id':
                    case 'class_id':
                    case 'object_identity_id':
                    case 'security_identity_id':
                    case 'ace_order':
                    case 'mask':
                        $resultRow[$name] = null !== $row[$name] ? (int)$row[$name] : null;
                        break;
                    case 'granting':
                        $resultRow[$name] = null !== $row[$name] ? (bool)$row[$name] : null;
                        break;
                    default:
                        $resultRow[$name] = $value;
                }
            }
            $result[] = $resultRow;
        }

        return $result;
    }

    private function updateAces(array $aces)
    {
        if (!$aces) {
            return;
        }

        $query = sprintf(
            'UPDATE %s SET mask = :mask WHERE id = :id',
            $this->entriesTableName
        );

        foreach ($aces as $ace) {
            $this->addSql(
                $query,
                ['mask' => $ace['mask'], 'id' => $ace['id']],
                ['mask' => Types::INTEGER, 'id' => Types::INTEGER]
            );
        }
    }

    /**
     * @param int[] $ids
     */
    private function deleteAces(array $ids)
    {
        if (!$ids) {
            return;
        }

        $query = sprintf(
            'DELETE FROM %s WHERE id IN (:ids)',
            $this->entriesTableName
        );

        $chunks = array_chunk($ids, 1000);
        foreach ($chunks as $chunk) {
            $this->addSql(
                $query,
                ['ids' => $chunk],
                ['ids' => Connection::PARAM_INT_ARRAY]
            );
        }
    }

    private function insertAces(array $aces)
    {
        if (!$aces) {
            return;
        }

        $query = sprintf(
            'INSERT INTO %s (class_id, object_identity_id, security_identity_id,'
            . ' ace_order, mask, granting, granting_strategy, audit_success, audit_failure)'
            . ' VALUES (:class_id, :object_identity_id, :security_identity_id,'
            . ' :ace_order, :mask, :granting, :granting_strategy, :audit_success, :audit_failure)',
            $this->entriesTableName
        );

        foreach ($aces as $ace) {
            $this->addSql(
                $query,
                $ace,
                [
                    'class_id'             => Types::INTEGER,
                    'object_identity_id'   => Types::INTEGER,
                    'security_identity_id' => Types::INTEGER,
                    'ace_order'            => Types::INTEGER,
                    'mask'                 => Types::INTEGER,
                    'granting'             => Types::BOOLEAN,
                    'granting_strategy'    => Types::STRING,
                    'audit_success'        => Types::BOOLEAN,
                    'audit_failure'        => Types::BOOLEAN
                ]
            );
        }
    }

    /**
     * @param array $aceOrders [id => order, ...]
     */
    private function updateAceOrders(array $aceOrders)
    {
        if (!$aceOrders) {
            return;
        }

        $query = sprintf(
            'UPDATE %s SET ace_order = :ace_order WHERE id = :id',
            $this->entriesTableName
        );

        foreach ($aceOrders as $id => $order) {
            $this->addSql(
                $query,
                ['ace_order' => $order, 'id' => $id],
                ['ace_order' => Types::INTEGER, 'id' => Types::INTEGER]
            );
        }
    }
}
