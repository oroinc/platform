<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUniqueVersionIndex implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->resolveDuplicates();

        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addUniqueIndex(['object_id', 'object_class', 'version'], 'idx_oro_audit_version');
    }

    protected function resolveDuplicates()
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $this->resolveDuplicatesPostgres();
        } else {
            $this->resolveDuplicatesMysql();
        }
    }

    private function resolveDuplicatesPostgres()
    {
        $this->connection->executeStatement('CREATE TEMPORARY SEQUENCE seq_temp_version START 1');

        while (true) {
            $rowsFound = $this->connection->executeQuery(
                'SELECT COUNT(*)
                    FROM oro_audit
                    GROUP BY object_id, object_class, version 
                    HAVING COUNT(*) > 1 LIMIT 1'
            )
                ->fetchOne();

            if (!$rowsFound) {
                break;
            }
            $this->connection->executeStatement(
                <<<'EOD'
                DO $$
                    DECLARE
                        r RECORD;
                        seq_temp INTEGER;   
                    BEGIN
                        FOR r IN SELECT object_id, object_class, version
                            FROM oro_audit
                            GROUP BY object_id, object_class, version 
                            HAVING COUNT(*) > 1 LIMIT 100
                        LOOP
                            seq_temp := (SELECT setval('seq_temp_version', 1));
                            IF r.object_id IS NULL THEN                                
                                UPDATE oro_audit SET version = nextval('seq_temp_version') - 1 
                                WHERE object_id IS NULL AND 
                                      object_class = r.object_class;
                            ELSE
                                UPDATE oro_audit SET version = nextval('seq_temp_version') - 1 
                                WHERE object_id = r.object_id AND 
                                      object_class = r.object_class;
                            END IF;
                        END LOOP;
                END $$;
EOD
            );
        }

        $this->connection->executeStatement('DROP SEQUENCE seq_temp_version');
    }

    private function resolveDuplicatesMysql()
    {
        while (true) {
            $sql = 'SELECT object_id, object_class FROM oro_audit '.
                'GROUP BY object_id, object_class, version HAVING COUNT(*) > 1 LIMIT 100';
            $rows = $this->connection->fetchAllAssociative($sql);
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $parameters = [
                    'object_id' => $row['object_id'],
                    'object_class' => $row['object_class'],
                ];
                $types = [
                    'object_id' => Types::INTEGER,
                    'object_class' => Types::STRING,
                ];

                $this->connection->executeStatement(
                    'UPDATE oro_audit SET version = 0 WHERE object_id = :object_id AND object_class = :object_class',
                    $parameters,
                    $types
                );
                $this->connection->executeStatement('SET @version = 0');
                $this->connection->executeStatement(
                    'UPDATE oro_audit SET version = @version:=@version+1 
                    WHERE object_id = :object_id AND object_class = :object_class',
                    $parameters,
                    $types
                );
            }
        }
    }
}
