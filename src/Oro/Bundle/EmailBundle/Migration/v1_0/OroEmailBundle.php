<?php

namespace Oro\Bundle\EmailBundle\Migration\v1_0;

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
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('from_email_address_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('folder_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('subject', 'string', ['default' => null, 'notnull' => true, 'length' => 500, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('from_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('received', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('sent', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('importance', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('internaldate', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('message_id', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('x_message_id', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('x_thread_id', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['folder_id'], 'IDX_2A30C171162CB942', []);
        $table->addIndex(['from_email_address_id'], 'IDX_2A30C171D445573A', []);
        /** End of generate table oro_email **/

        /** Generate table oro_email_address **/
        $table = $schema->createTable('oro_email_address');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('created', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('email', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('has_owner', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'oro_email_address_uq');
        /** End of generate table oro_email_address **/

        /** Generate table oro_email_attachment **/
        $table = $schema->createTable('oro_email_attachment');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('body_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('file_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content_type', 'string', ['default' => null, 'notnull' => true, 'length' => 100, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['body_id'], 'IDX_F4427F239B621D84', []);
        /** End of generate table oro_email_attachment **/

        /** Generate table oro_email_attachment_content **/
        $table = $schema->createTable('oro_email_attachment_content');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('attachment_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content_transfer_encoding', 'string', ['default' => null, 'notnull' => true, 'length' => 20, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id'], 'UNIQ_18704959464E68B');
        /** End of generate table oro_email_attachment_content **/

        /** Generate table oro_email_body **/
        $table = $schema->createTable('oro_email_body');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('email_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('body', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('body_is_text', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('has_attachments', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('persistent', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_C7CE120DA832C1C9', []);
        /** End of generate table oro_email_body **/

        /** Generate table oro_email_folder **/
        $table = $schema->createTable('oro_email_folder');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('origin_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('full_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => true, 'length' => 10, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('synchronized', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['origin_id'], 'IDX_EB940F1C56A273CC', []);
        /** End of generate table oro_email_folder **/

        /** Generate table oro_email_origin **/
        $table = $schema->createTable('oro_email_origin');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('isActive', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('sync_code_updated', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('synchronized', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('sync_code', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('internal_name', 'string', ['default' => null, 'notnull' => false, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_email_origin **/

        /** Generate table oro_email_recipient **/
        $table = $schema->createTable('oro_email_recipient');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('email_address_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('email_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => true, 'length' => 3, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id'], 'IDX_7DAF9656A832C1C9', []);
        $table->addIndex(['email_address_id'], 'IDX_7DAF965659045DAA', []);
        /** End of generate table oro_email_recipient **/

        /** Generate table oro_email_template **/
        $table = $schema->createTable('oro_email_template');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('isSystem', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('isEditable', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('parent', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('subject', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entityName', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => true, 'length' => 20, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'entityName'], 'UQ_NAME');
        $table->addIndex(['name'], 'email_name_idx', []);
        $table->addIndex(['isSystem'], 'email_is_system_idx', []);
        $table->addIndex(['entityName'], 'email_entity_name_idx', []);
        /** End of generate table oro_email_template **/

        /** Generate table oro_email_template_translation **/
        $table = $schema->createTable('oro_email_template_translation');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('object_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('locale', 'string', ['default' => null, 'notnull' => true, 'length' => 8, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
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
