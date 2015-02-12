<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add additional fields
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        $table->addColumn('is_head', 'boolean', []);
        $table->addColumn('thread_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('refs', 'text', ['notnull' => false]);
    }
}
