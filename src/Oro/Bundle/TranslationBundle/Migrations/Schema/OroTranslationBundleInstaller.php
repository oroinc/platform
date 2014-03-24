<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroCRM\Bundle\ContactBundle\Migrations\Schema\v1_0\OroCRMContactBundle;

class OroTranslationBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_translation **/
        $table = $schema->createTable('oro_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 255]);
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 5]);
        $table->addColumn('domain', 'string', ['length' => 255]);
        $table->addColumn('scope', 'smallint', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'domain'], 'MESSAGES_IDX', []);
        $table->addIndex(['`key`'], 'MESSAGE_IDX', []);
        /** End of generate table oro_translation **/
    }
}
