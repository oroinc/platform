<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateSnapshot implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_segment_snapshot');
        $table->changeColumn('entity_id', ['type' => StringType::getType('string')]);
    }
}
