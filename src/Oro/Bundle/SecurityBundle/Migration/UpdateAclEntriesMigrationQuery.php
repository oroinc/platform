<?php

namespace Oro\Bundle\SecurityBundle\Migration;

use Doctrine\DBAL\Connection;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

class UpdateAclEntriesMigrationQuery implements MigrationQuery
{
    /** @var Connection */
    protected $connection;

    /** @var AclManager */
    protected $aclManager;

    /** @var EntityAclExtension */
    protected $aclExtension;

    /** @var AclCacheInterface */
    protected $aclCache;

    /** @var string */
    protected $entriesTableName;

    /** @var string */
    protected $objectIdentitiesTableName;

    /** @var string */
    protected $aclClassesTableName;

    /** @var string[] */
    protected $queries = [];

    /** @var array */
    protected $masks = [
        'MASK_VIEW_BASIC'    => 1,
        'MASK_CREATE_BASIC'  => 2,
        'MASK_EDIT_BASIC'    => 4,
        'MASK_DELETE_BASIC'  => 8,
        'MASK_ASSIGN_BASIC'  => 16,
        'MASK_SHARE_BASIC'   => 32,
        'MASK_VIEW_LOCAL'    => 64,
        'MASK_CREATE_LOCAL'  => 128,
        'MASK_EDIT_LOCAL'    => 256,
        'MASK_DELETE_LOCAL'  => 512,
        'MASK_ASSIGN_LOCAL'  => 1024,
        'MASK_SHARE_LOCAL'   => 2048,
        'MASK_VIEW_DEEP'     => 4096,
        'MASK_CREATE_DEEP'   => 8192,
        'MASK_EDIT_DEEP'     => 16384,
        'MASK_DELETE_DEEP'   => 32768,
        'MASK_ASSIGN_DEEP'   => 65536,
        'MASK_SHARE_DEEP'    => 131072,
        'MASK_VIEW_GLOBAL'   => 262144,
        'MASK_CREATE_GLOBAL' => 524288,
        'MASK_EDIT_GLOBAL'   => 1048576,
        'MASK_DELETE_GLOBAL' => 2097152,
        'MASK_ASSIGN_GLOBAL' => 4194304,
        'MASK_SHARE_GLOBAL'  => 8388608,
        'MASK_VIEW_SYSTEM'   => 16777216,
        'MASK_CREATE_SYSTEM' => 33554432,
        'MASK_EDIT_SYSTEM'   => 67108864,
        'MASK_DELETE_SYSTEM' => 134217728,
        'MASK_ASSIGN_SYSTEM' => 268435456,
        'MASK_SHARE_SYSTEM'  => 536870912,
    ];

    /**
     * @param Connection $connection
     * @param AclManager $aclManager
     * @param AclCacheInterface $aclCache
     * @param string $entriesTableName
     * @param string $objectIdentitiesTableName
     * @param string $aclClassesTableName
     */
    public function __construct(
        Connection $connection,
        AclManager $aclManager,
        AclCacheInterface $aclCache,
        $entriesTableName,
        $objectIdentitiesTableName,
        $aclClassesTableName
    ) {
        $this->connection = $connection;
        $this->aclManager = $aclManager;
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
        return 'Update all ACE`s mask to support EntityMaskBuilder with dynamical identities';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT e.*
                FROM %s AS o
                INNER JOIN %s AS c ON c.id = o.class_id
                LEFT JOIN %s AS e ON e.class_id = o.class_id
                  AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL)
                WHERE o.object_identifier = %s';

        $rows = $this->connection->fetchAll(
            sprintf(
                $sql,
                $this->objectIdentitiesTableName,
                $this->aclClassesTableName,
                $this->entriesTableName,
                $this->connection->quote(EntityAclExtension::NAME)
            )
        );

        $groupedAces = [];

        foreach ($rows as $row) {
            $key = sprintf('%s-%s-%s', $row['class_id'], $row['object_identity_id'], $row['field_name']);

            $groupedAces[$key][$row['ace_order']] = $row;
        }

        $forUpdate = [];
        $forInsert = [];

        foreach ($groupedAces as $key => $aces) {
            foreach ($aces as $aceOrder => $ace) {
                $newMasks = $this->processMask($ace['mask']);

                $ace['mask'] = $newMasks[0];

                $forUpdate[$key][$aceOrder] = $ace;
                $groupedAces[$key][$aceOrder] = $ace;

                for ($i = 1; $i < count($newMasks); $i++) {
                    $newAceOrder = max(array_keys($groupedAces[$key])) + 1;

                    $ace['mask'] = $newMasks[$i];
                    $ace['ace_order'] = $newAceOrder;

                    $forInsert[$key][$newAceOrder] = $ace;
                    $groupedAces[$key][$newAceOrder] = $ace;
                }
            }
        }

        $this->updateAces($forUpdate);
        $this->insertAces($forInsert);

        foreach ($this->queries as $query) {
            $logger->notice($query);
            $this->connection->executeUpdate($query);
        }

        $this->aclCache->clearCache();
    }

    /**
     * @param int[] $masks
     * @param ObjectIdentity $oid
     */
    protected function validateMasks($masks, ObjectIdentity $oid)
    {
        foreach ($masks as $mask) {
            $this->getAclExtension()->validateMask($mask, $oid);
        }
    }

    /**
     * @param array $aces
     */
    protected function updateAces(array $aces)
    {
        $sql = 'UPDATE %s SET mask = %d WHERE id = %d';

        foreach ($aces as $rows) {
            foreach ($rows as $ace) {
                $this->addSql(sprintf($sql, $this->entriesTableName, $ace['mask'], $ace['id']));
            }
        }
    }

    /**
     * @param array $aces
     */
    protected function insertAces(array $aces)
    {
        $sql = 'INSERT INTO %s (
                  class_id,
                  object_identity_id,
                  field_name,
                  ace_order,
                  security_identity_id,
                  mask,
                  granting,
                  granting_strategy,
                  audit_success,
                  audit_failure
                )
                VALUES (%d, %s, %s, %d, %d, %d, %s, %s, %s, %s)';

        $databasePlatform = $this->connection->getDatabasePlatform();

        foreach ($aces as $rows) {
            foreach ($rows as $ace) {
                $this->addSql(
                    sprintf(
                        $sql,
                        $this->entriesTableName,
                        $ace['class_id'],
                        null === $ace['object_identity_id'] ? 'NULL' : (int)$ace['object_identity_id'],
                        null === $ace['field_name'] ? 'NULL' : $this->connection->quote($ace['field_name']),
                        $ace['ace_order'],
                        $ace['security_identity_id'],
                        $ace['mask'],
                        $databasePlatform->convertBooleans($ace['granting']),
                        $this->connection->quote($ace['granting_strategy']),
                        $databasePlatform->convertBooleans($ace['audit_success']),
                        $databasePlatform->convertBooleans($ace['audit_failure'])
                    )
                );
            }
        }
    }

    /**
     * @param int $mask
     * @return array
     */
    protected function processMask($mask)
    {
        /** @var EntityMaskBuilder[] $maskBuilders */
        $maskBuilders = $this->getAclExtension()->getAllMaskBuilders();

        foreach ($this->masks as $maskName => $oldMask) {
            if (($mask & $oldMask) > 0) {
                foreach ($maskBuilders as $maskBuilder) {
                    if ($maskBuilder->hasMask($maskName)) {
                        $maskBuilder->add(str_replace('MASK_', '', $maskName));
                        break;
                    }
                }
            }
        }

        return array_map(
            function (EntityMaskBuilder $maskBuilder) {
                return $maskBuilder->get();
            },
            $maskBuilders
        );
    }

    /**
     * @return EntityAclExtension
     */
    protected function getAclExtension()
    {
        if (!$this->aclExtension) {
            $this->aclExtension = $this->aclManager->getExtensionSelector()->select('entity:(root)');
        }

        return $this->aclExtension;
    }

    /**
     * @param string $query
     */
    public function addSql($query)
    {
        $this->queries[] = $query;
    }
}
