<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroEmailBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_email **/
        $table = $schema->createTable('oro_email');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('from_email_address_id', 'integer', []);
        $table->addColumn('folder_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('subject', 'string', ['length' => 500]);
        $table->addColumn('from_name', 'string', ['length' => 255]);
        $table->addColumn('received', 'datetime', []);
        $table->addColumn('sent', 'datetime', []);
        $table->addColumn('importance', 'integer', []);
        $table->addColumn('internaldate', 'datetime', []);
        $table->addColumn('message_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('x_message_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('x_thread_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['folder_id'], 'IDX_2A30C171162CB942', []);
        $table->addIndex(['from_email_address_id'], 'IDX_2A30C171D445573A', []);
        /** End of generate table oro_email **/

        /** Generate table oro_email_address **/
        $table = $schema->createTable('oro_email_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('has_owner', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'oro_email_address_uq');
        /** End of generate table oro_email_address **/

        /** Generate table oro_email_attachment **/
        $table = $schema->createTable('oro_email_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('body_id', 'integer', ['notnull' => false]);
        $table->addColumn('file_name', 'string', ['length' => 255]);
        $table->addColumn('content_type', 'string', ['length' => 100]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['body_id'], 'IDX_F4427F239B621D84', []);
        /** End of generate table oro_email_attachment **/

        /** Generate table oro_email_attachment_content **/
        $table = $schema->createTable('oro_email_attachment_content');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer', []);
        $table->addColumn('content', 'text', []);
        $table->addColumn('content_transfer_encoding', 'string', ['length' => 20]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id'], 'UNIQ_18704959464E68B');
        /** End of generate table oro_email_attachment_content **/

        /** Generate table oro_email_body **/
        $table = $schema->createTable('oro_email_body');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('body', 'text', []);
        $table->addColumn('body_is_text', 'boolean', []);
        $table->addColumn('has_attachments', 'boolean', []);
        $table->addColumn('persistent', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_C7CE120DA832C1C9', []);
        /** End of generate table oro_email_body **/

        /** Generate table oro_email_folder **/
        $table = $schema->createTable('oro_email_folder');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('full_name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 10]);
        $table->addColumn('synchronized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['origin_id'], 'IDX_EB940F1C56A273CC', []);
        /** End of generate table oro_email_folder **/

        /** Generate table oro_email_origin **/
        $table = $schema->createTable('oro_email_origin');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('isActive', 'boolean', []);
        $table->addColumn('sync_code_updated', 'datetime', ['notnull' => false]);
        $table->addColumn('synchronized', 'datetime', ['notnull' => false]);
        $table->addColumn('sync_code', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 30]);
        $table->addColumn('internal_name', 'string', ['notnull' => false, 'length' => 30]);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_email_origin **/

        /** Generate table oro_email_recipient **/
        $table = $schema->createTable('oro_email_recipient');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_address_id', 'integer', []);
        $table->addColumn('email_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_7DAF9656A832C1C9', []);
        $table->addIndex(['email_address_id'], 'IDX_7DAF965659045DAA', []);
        /** End of generate table oro_email_recipient **/

        /** Generate table oro_email_template **/
        $table = $schema->createTable('oro_email_template');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('isSystem', 'boolean', []);
        $table->addColumn('isEditable', 'boolean', []);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent', 'integer', ['notnull' => false]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('entityName', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'entityName'], 'UQ_NAME');
        $table->addIndex(['name'], 'email_name_idx', []);
        $table->addIndex(['isSystem'], 'email_is_system_idx', []);
        $table->addIndex(['entityName'], 'email_entity_name_idx', []);
        /** End of generate table oro_email_template **/

        /** Generate table oro_email_template_translation **/
        $table = $schema->createTable('oro_email_template_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['object_id'], 'IDX_F42DCDB8232D562B', []);
        $table->addIndex(['locale', 'object_id', 'field'], 'lookup_unique_idx', []);
        /** End of generate table oro_email_template_translation **/

        /** Generate foreign keys for table oro_email **/
        $table = $schema->getTable('oro_email');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_address'), ['from_email_address_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_email_folder'), ['folder_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email **/

        /** Generate foreign keys for table oro_email_attachment **/
        $table = $schema->getTable('oro_email_attachment');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_body'), ['body_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_attachment **/

        /** Generate foreign keys for table oro_email_attachment_content **/
        $table = $schema->getTable('oro_email_attachment_content');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_attachment'), ['attachment_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_attachment_content **/

        /** Generate foreign keys for table oro_email_body **/
        $table = $schema->getTable('oro_email_body');
        $table->addForeignKeyConstraint($schema->getTable('oro_email'), ['email_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_body **/

        /** Generate foreign keys for table oro_email_folder **/
        $table = $schema->getTable('oro_email_folder');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_origin'), ['origin_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_folder **/

        /** Generate foreign keys for table oro_email_recipient **/
        $table = $schema->getTable('oro_email_recipient');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_address'), ['email_address_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_email'), ['email_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_recipient **/

        /** Generate foreign keys for table oro_email_template_translation **/
        $table = $schema->getTable('oro_email_template_translation');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_template'), ['object_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_template_translation **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
