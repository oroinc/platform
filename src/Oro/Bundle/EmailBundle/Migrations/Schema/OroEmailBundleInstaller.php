<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_35\EmailMessageIdIndexQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * ORO installer for EmailBundle
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroEmailBundleInstaller implements Installation, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_37';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroEmailToFolderRelationTable($schema);
        $this->createOroEmailAddressTable($schema);
        $this->createOroEmailAttachmentTable($schema);
        $this->createOroEmailRecipientTable($schema);
        $this->createOroEmailTemplateTable($schema);
        $this->createOroEmailAttachmentContentTable($schema);
        $this->createOroEmailOriginTable($schema);
        $this->createOroEmailFolderTable($schema);
        $this->createOroEmailUserTable($schema);
        $this->createOroEmailThreadTable($schema);
        $this->createOroEmailBodyTable($schema);
        $this->createOroEmailMailboxTable($schema, $queries);
        $this->createOroEmailMailboxProcessTable($schema);
        $this->createOroEmailTable($schema);
        $this->createOroEmailAutoResponseRuleTable($schema);
        $this->createOroEmailTemplateLocalizedTable($schema);
        $this->createOroEmailAddressVisibilityTable($schema);

        /** Foreign keys generation **/
        $this->addOroEmailToFolderRelationForeignKeys($schema);
        $this->addOroEmailAddressForeignKeys($schema);
        $this->addOroEmailAttachmentForeignKeys($schema);
        $this->addOroEmailRecipientForeignKeys($schema);
        $this->addOroEmailTemplateForeignKeys($schema);
        $this->addOroEmailAttachmentContentForeignKeys($schema);
        $this->addOroEmailFolderForeignKeys($schema);
        $this->addOroEmailUserForeignKeys($schema);
        $this->addOroEmailThreadForeignKeys($schema);
        $this->addOroEmailMailboxForeignKeys($schema);
        $this->addOroEmailForeignKeys($schema);
        $this->addOroEmailAutoResponseRuleForeignKeys($schema);
        $this->addOroEmailTemplateLocalizedForeignKeys($schema);

        $queries->addPostQuery(new EmailMessageIdIndexQuery());
    }

    /**
     * Create many-to-many relation table
     */
    public static function createOroEmailToFolderRelationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_to_folder');
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('emailfolder_id', 'integer', []);
        $table->addIndex(['email_id'], 'oro_folder_email_idx', []);
        $table->addIndex(['emailfolder_id'], 'oro_email_folder_idx', []);
        $table->setPrimaryKey(['email_id', 'emailfolder_id']);
    }

    /**
     * Generate table oro_email_address
     */
    public static function createOroEmailAddressTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('has_owner', 'boolean', []);
        $table->addColumn('owner_mailbox_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'oro_email_address_uq');
    }

    /**
     * Create oro_email_attachment table
     */
    protected function createOroEmailAttachmentTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('body_id', 'integer', ['notnull' => false]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('file_name', 'string', ['length' => 255]);
        $table->addColumn('content_type', 'string', ['length' => 100]);
        $table->addColumn('embedded_content_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_recipient table
     */
    protected function createOroEmailRecipientTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_recipient');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_address_id', 'integer');
        $table->addColumn('email_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 320]);
        $table->addColumn('type', 'string', ['length' => 3]);
        $table->addIndex(['email_id', 'type'], 'email_id_type_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_template table
     */
    protected function createOroEmailTemplateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_template');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('issystem', 'boolean');
        $table->addColumn('iseditable', 'boolean');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent', 'integer', ['notnull' => false]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('entityname', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'email_name_idx');
        $table->addIndex(['entityname'], 'email_entity_name_idx');
        $table->addIndex(['issystem'], 'email_is_system_idx');
        $table->addUniqueIndex(['name', 'entityname'], 'uq_name');
    }

    /**
     * Create oro_email_attachment_content table
     */
    protected function createOroEmailAttachmentContentTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_attachment_content');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer');
        $table->addColumn('content', 'text');
        $table->addColumn('content_transfer_encoding', 'string', ['length' => 20]);
        $table->addUniqueIndex(['attachment_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_origin table
     */
    protected function createOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_origin');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('isactive', 'boolean');
        $table->addColumn('is_sync_enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('sync_code_updated', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('synchronized', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('sync_code', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 30]);
        $table->addColumn('internal_name', 'string', ['notnull' => false, 'length' => 30]);
        $table->addColumn('sync_count', 'integer', ['notnull' => false]);
        $table->addColumn('mailbox_name', 'string', ['length' => 64, 'default' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['isactive', 'name'], 'isactive_name_idx');
        $table->addIndex(['mailbox_name'], 'idx_mailbox_name');
    }

    /**
     * Create oro_email_folder table
     */
    protected function createOroEmailFolderTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_folder');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_folder_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('full_name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 10]);
        $table->addColumn('synchronized', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('outdated_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('sync_enabled', 'boolean', ['default' => false]);
        $table->addColumn('sync_start_date', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('failed_count', 'integer', ['default' => 0]);
        $table->addIndex(['outdated_at'], 'email_folder_outdated_at_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_user table
     */
    protected function createOroEmailUserTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('folder_id', 'integer', ['notnull' => true]);
        $table->addColumn('email_id', 'integer', ['notnull' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('received', 'datetime');
        $table->addColumn('is_seen', 'boolean', ['default' => true]);
        $table->addColumn('is_private', 'boolean', ['notnull' => false]);
        $table->addColumn('mailbox_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('unsyncedflagcount', 'integer', ['default' => 0]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['is_seen', 'mailbox_owner_id'], 'seen_idx');
        $table->addIndex(['received', 'is_seen', 'mailbox_owner_id'], 'received_idx');
    }

    /**
     * Create oro_email_thread table
     */
    protected function createOroEmailThreadTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_thread');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('last_unseen_email_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_body table
     */
    protected function createOroEmailBodyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_body');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('body', 'text');
        $table->addColumn('body_is_text', 'boolean');
        $table->addColumn('has_attachments', 'boolean');
        $table->addColumn('persistent', 'boolean');
        $table->addColumn('text_body', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_mailbox table
     */
    protected function createOroEmailMailboxTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_email_mailbox');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('process_settings_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['email']);
        $table->addUniqueIndex(['label']);
        $table->addUniqueIndex(['process_settings_id']);
        $table->addUniqueIndex(['origin_id']);
        $table->setPrimaryKey(['id']);

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_mailbox_email_ci ON oro_email_mailbox (LOWER(email))'
            ));
        }
    }

    /**
     * Create oro_email_mailbox_process table
     */
    protected function createOroEmailMailboxProcessTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_process');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email table
     */
    protected function createOroEmailTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('from_email_address_id', 'integer');
        $table->addColumn('thread_id', 'integer', ['notnull' => false]);
        $table->addColumn('email_body_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('subject', 'string', ['length' => 998]);
        $table->addColumn('from_name', 'string', ['length' => 320]);
        $table->addColumn('sent', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('importance', 'integer');
        $table->addColumn('internaldate', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('message_id', 'string', ['length' => 512]);
        $table->addColumn('x_message_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('x_thread_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_head', 'boolean', ['default' => true]);
        $table->addColumn('refs', 'text', ['notnull' => false]);
        $table->addColumn('multi_message_id', 'text', ['notnull' => false]);
        $table->addColumn('acceptlanguageheader', 'text', ['notnull' => false]);
        $table->addColumn('body_synced', 'boolean', ['default' => false, 'notnull' => false]);
        $table->addIndex(['sent'], 'idx_sent');
        $table->addIndex(['is_head'], 'oro_email_is_head');
        $table->addUniqueIndex(['email_body_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_auto_response_rule table
     */
    protected function createOroEmailAutoResponseRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_auto_response_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('mailbox_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('active', 'boolean');
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('definition', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email_template_localized table
     */
    protected function createOroEmailTemplateLocalizedTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_template_localized');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => true]);
        $table->addColumn('localization_id', 'integer', ['notnull' => true]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('subject_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('content_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create many-to-many relation table
     */
    public static function addOroEmailToFolderRelationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_to_folder');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['emailfolder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_address foreign keys.
     */
    protected function addOroEmailAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['owner_mailbox_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_attachment foreign keys.
     */
    protected function addOroEmailAttachmentForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_attachment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_body'),
            ['body_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_recipient foreign keys.
     */
    protected function addOroEmailRecipientForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_recipient');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_address'),
            ['email_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_template foreign keys.
     */
    protected function addOroEmailTemplateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_template');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_email_attachment_content foreign keys.
     */
    protected function addOroEmailAttachmentContentForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_attachment_content');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_attachment'),
            ['attachment_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_folder foreign keys.
     */
    protected function addOroEmailFolderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['parent_folder_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_user foreign keys.
     */
    protected function addOroEmailUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF6162CB942'
        );
    }

    /**
     * Add oro_email_thread foreign keys.
     */
    protected function addOroEmailThreadForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_thread');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['last_unseen_email_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_email_mailbox foreign keys.
     */
    protected function addOroEmailMailboxForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_mailbox');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox_process'),
            ['process_settings_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_email foreign keys.
     */
    protected function addOroEmailForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_body'),
            ['email_body_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_address'),
            ['from_email_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_thread'),
            ['thread_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_email_auto_response_rule foreign keys.
     */
    protected function addOroEmailAutoResponseRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_auto_response_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_template_localized foreign keys.
     */
    protected function addOroEmailTemplateLocalizedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_template_localized');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function createOroEmailAddressVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_address_visibility');
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => true]);
        $table->addColumn('is_visible', 'boolean', []);
        $table->setPrimaryKey(['email', 'organization_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
