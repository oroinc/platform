<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateSegmentSnapshotTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_segment_snapshot');
        $table->addColumn('integer_entity_id', 'integer', ['notnull' => false]);
        $table->changeColumn('entity_id', ['notnull' => false]);
        $table->addIndex(['integer_entity_id'], 'sgmnt_snpsht_int_entity_idx');
        $table->addIndex(['entity_id'], 'sgmnt_snpsht_str_entity_idx');

        $queries->addQuery(new UpdateSegmentSnapshotDataQuery());
    }
}
