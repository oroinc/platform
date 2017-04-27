<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ReplaceNamespaces implements Migration, ConnectionAwareInterface
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
        $this->resolveVersions();

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_audit',
            'object_class',
            'OroCRM',
            'Oro'
        ));
    }

    public function resolveVersions()
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSqlPlatform) {
            $this->connection->exec('CREATE TEMPORARY SEQUENCE seq_temp_version START 1');
        }

        while (true) {
            $sql = 'SELECT object_id, REPLACE(object_class, \'OroCRM\', \'Oro\') AS object_class FROM oro_audit ' .
                'GROUP BY object_id, REPLACE(object_class, \'OroCRM\', \'Oro\'), version HAVING COUNT(*) > 1 LIMIT 25';
            $rows = $this->connection->fetchAll($sql);
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                if ($platform instanceof PostgreSqlPlatform) {
                    $sql = 'UPDATE oro_audit SET version = 0 ' .
                        'WHERE object_id = :object_id AND '.
                        'REPLACE(object_class, \'OroCRM\', \'Oro\') = :object_class;' .
                        'SELECT setval(\'seq_temp_version\', 1);' .
                        'UPDATE oro_audit SET version = nextval(\'seq_temp_version\') - 1 ' .
                        'WHERE object_id = :object_id AND '.
                        'REPLACE(object_class, \'OroCRM\', \'Oro\') = :object_class;';
                } else {
                    $sql = 'UPDATE oro_audit SET version = 0 ' .
                        'WHERE object_id = :object_id AND '.
                        'REPLACE(object_class, \'OroCRM\', \'Oro\') = :object_class;'.
                        'SET @version = 0;'.
                        'UPDATE oro_audit SET version = @version:=@version+1 '.
                        'WHERE object_id = :object_id AND '.
                        'REPLACE(object_class, \'OroCRM\', \'Oro\') = :object_class;';
                }

                $this->connection->executeUpdate(
                    $sql,
                    [
                        'object_id' => $row['object_id'],
                        'object_class' => $row['object_class'],
                    ],
                    [
                        'object_id' => 'integer',
                        'object_class' => 'string',
                    ]
                );
            }
        }

        if ($platform instanceof PostgreSqlPlatform) {
            $this->connection->exec('DROP SEQUENCE seq_temp_version');
        }
    }
}
