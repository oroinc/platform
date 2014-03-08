<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmbeddedFormBundle extends Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_embedded_form **/
        $table = $schema->createTable('oro_embedded_form');
        $table->addColumn('id', 'string', ['length' => 255]);
        $table->addColumn('channel_id', 'smallint', ['notnull' => false]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('css', 'text', []);
        $table->addColumn('form_type', 'string', ['length' => 255]);
        $table->addColumn('success_message', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_F7A34C172F5A1AA', []);
        /** End of generate table oro_embedded_form **/

        /** Generate foreign keys for table oro_embedded_form **/
        $table = $schema->getTable('oro_embedded_form');
        $table->addForeignKeyConstraint($schema->getTable('oro_integration_channel'), ['channel_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_embedded_form **/

        // @codingStandardsIgnoreEnd
    }
}
