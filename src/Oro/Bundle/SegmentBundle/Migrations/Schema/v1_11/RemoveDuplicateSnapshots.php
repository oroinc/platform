<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveDuplicateSnapshots implements Migration, ConnectionAwareInterface
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

    public function up(Schema $schema, QueryBag $queries)
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->removeDuplicateSnapshots($platform, $queries);
        $this->addUniqueKey($schema);
    }

    private function addUniqueKey(Schema $schema): void
    {
        $table = $schema->getTable('oro_segment_snapshot');
        $table->addUniqueIndex(
            ['segment_id', 'integer_entity_id'],
            'oro_segment_snapshot_segment_id_integer_entity_id_idx'
        );
    }

    private function removeDuplicateSnapshots(
        AbstractPlatform $platform,
        QueryBag $queries
    ): void
    {
        if ($platform instanceof PostgreSQL94Platform) {
            $sql = <<<SQL
            DELETE FROM oro_segment_snapshot oro_segment_snapshot_1
            USING oro_segment_snapshot oro_segment_snapshot_2
            WHERE
                oro_segment_snapshot_1.id < oro_segment_snapshot_2.id
                AND oro_segment_snapshot_1.segment_id = oro_segment_snapshot_2.segment_id
                AND oro_segment_snapshot_1.integer_entity_id = oro_segment_snapshot_2.integer_entity_id
        SQL;
        } else {
            $sql = <<<SQL
            DELETE FROM oro_segment_snapshot_1
                USING oro_segment_snapshot oro_segment_snapshot_2 JOIN oro_segment_snapshot oro_segment_snapshot_1
                WHERE
                    oro_segment_snapshot_1.id < oro_segment_snapshot_2.id
                    AND oro_segment_snapshot_1.segment_id = oro_segment_snapshot_2.segment_id
                    AND oro_segment_snapshot_1.integer_entity_id = oro_segment_snapshot_2.integer_entity_id
        SQL;
        }
        $queries->addPreQuery($sql);
    }
}
