<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateRememberMeTokenTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('rememberme_token')) {
            return;
        }
        $table = $schema->createTable('rememberme_token');
        $table->addColumn('series', 'string', ['fixed' => true, 'length' => 88]);
        $table->addColumn('value', 'string', ['length' => 88]);
        $table->addColumn('lastUsed', 'datetime', []);
        $table->addColumn('class', 'string', ['length' => 255]);
        $table->addColumn('username', 'string', ['length' => 255]);
        $table->setPrimaryKey(['series']);
    }
}
