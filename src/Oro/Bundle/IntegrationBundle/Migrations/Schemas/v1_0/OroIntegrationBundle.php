<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schemas\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_integration_channel **/
        $table = $schema->createTable('oro_integration_channel');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('transport_id', 'smallint', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('connectors', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['transport_id'], 'UNIQ_55B9B9C59909C13F');
        /** End of generate table oro_integration_channel **/

        /** Generate table oro_integration_channel_status **/
        $table = $schema->createTable('oro_integration_channel_status');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'smallint', []);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('connector', 'string', ['length' => 255]);
        $table->addColumn('message', 'text', []);
        $table->addColumn('date', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_C0D7E5FB72F5A1AA', []);
        /** End of generate table oro_integration_channel_status **/

        /** Generate table oro_integration_transport **/
        $table = $schema->createTable('oro_integration_transport');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->addColumn('wsdl_url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('api_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('sync_start_date', 'date', ['notnull' => false]);
        $table->addColumn('sync_range', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('websites', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('is_extension_installed', 'boolean', ['notnull' => false]);
        $table->addColumn('is_wsi_mode', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_integration_transport **/

        /** Generate foreign keys for table oro_integration_channel **/
        $table = $schema->getTable('oro_integration_channel');
        $table->addForeignKeyConstraint($schema->getTable('oro_integration_transport'), ['transport_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_integration_channel **/

        /** Generate foreign keys for table oro_integration_channel_status **/
        $table = $schema->getTable('oro_integration_channel_status');
        $table->addForeignKeyConstraint($schema->getTable('oro_integration_channel'), ['channel_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_integration_channel_status **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
