<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateLengthSnapshotSegmentIdColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema
            ->getTable('oro_segment_snapshot')
            ->changeColumn('id', ['type' => BigIntType::getType('bigint')]);
    }
}
