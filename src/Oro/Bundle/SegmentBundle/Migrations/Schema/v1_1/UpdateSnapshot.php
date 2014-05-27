<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_0;


use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Doctrine\DBAL\Types\StringType;

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
