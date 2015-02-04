<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_5;

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
        $table = $schema->getTable('oro_email');
        $table->addColumn('direction', 'integer', []);
        $table->addColumn('refs', 'text', ['notnull' => false]);
    }
}
