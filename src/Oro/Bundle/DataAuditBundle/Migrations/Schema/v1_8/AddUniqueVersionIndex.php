<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUniqueVersionIndex implements Migration, ConnectionAwareInterface
{
    /** @var Connection */
    private $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $ids = $this->getDuplicates();
        if ($ids) {
            $queries->addPreQuery(
                new ParametrizedSqlMigrationQuery(
                    'DELETE FROM oro_audit WHERE id IN (:ids)',
                    ['ids' => $ids],
                    ['ids' => Connection::PARAM_STR_ARRAY]
                )
            );
        }

        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addUniqueIndex(['object_id', 'object_class', 'version'], 'idx_oro_audit_version');
    }

    /**
     * @return string[]
     */
    protected function getDuplicates()
    {
        $sql = 'SELECT MAX(id) AS id FROM oro_audit GROUP BY object_id, object_class, version HAVING COUNT(*) > 1';

        $result = [];
        $rows   = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }

        return $result;
    }
}
