<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSegmentBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_segment')
            ->addColumn('records_limit', 'integer', ['notnull' => false]);
    }
}
