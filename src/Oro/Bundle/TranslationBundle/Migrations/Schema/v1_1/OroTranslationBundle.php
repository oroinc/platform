<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_translation');
        $table->dropIndex('MESSAGE_IDX');
        $table->dropColumn('`key`');

        $table->addColumn('key', 'string', ['length' => 255]);
        $table->addIndex(['locale', 'domain'], 'MESSAGES_IDX', []);
        $table->addIndex(['`key`'], 'MESSAGE_IDX', []);
    }
}
