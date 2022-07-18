<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveDuplicateSnapshots implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->removeDuplicateSnapshots($queries);
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

    private function removeDuplicateSnapshots(QueryBag $queries): void
    {
        if ($this->platform instanceof MySqlPlatform) {
            $sql = <<<SQL
                DELETE oro_segment_snapshot_1 FROM oro_segment_snapshot oro_segment_snapshot_1
                INNER JOIN oro_segment_snapshot oro_segment_snapshot_2
                WHERE
                    oro_segment_snapshot_1.id < oro_segment_snapshot_2.id
                    AND oro_segment_snapshot_1.segment_id = oro_segment_snapshot_2.segment_id
                    AND oro_segment_snapshot_1.integer_entity_id = oro_segment_snapshot_2.integer_entity_id
            SQL;
        } else {
            $sql = <<<SQL
                DELETE FROM oro_segment_snapshot oro_segment_snapshot_1
                USING oro_segment_snapshot oro_segment_snapshot_2
                WHERE
                    oro_segment_snapshot_1.id < oro_segment_snapshot_2.id
                    AND oro_segment_snapshot_1.segment_id = oro_segment_snapshot_2.segment_id
                    AND oro_segment_snapshot_1.integer_entity_id = oro_segment_snapshot_2.integer_entity_id
            SQL;
        }

        $queries->addPreQuery($sql);
    }
}
