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
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroEmailBundleInstaller implements Installation, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_37';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
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
        $this->addOroEmailAddressVisibilityForeignKeys($schema);

        $queries->addPostQuery(new EmailMessageIdIndexQuery());
    }

    /**
     * Create oro_email_address table
     */
    private function createOroEmailAddressTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime');
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('has_owner', 'boolean');
        $table->addColumn('owner_mailbox_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'oro_email_address_uq');
    }

    /**
     * Create oro_email_attachment table
     */
    private function createOroEmailAttachmentTable(Schema $schema): void
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
    private function createOroEmailRecipientTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_recipient');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email_address_id', 'integer');
        $table->addColumn('email_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 320]);
        $table->addColumn('type', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id', 'type'], 'email_id_type_idx');
    }

    /**
     * Create oro_email_template table
     */
    private function createOroEmailTemplateTable(Schema $schema): void
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
    private function createOroEmailAttachmentContentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_attachment_content');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attachment_id', 'integer');
        $table->addColumn('content', 'text');
        $table->addColumn('content_transfer_encoding', 'string', ['length' => 20]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['attachment_id']);
    }

    /**
     * Create oro_email_origin table
     */
    private function createOroEmailOriginTable(Schema $schema): void
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
    private function createOroEmailFolderTable(Schema $schema): void
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['outdated_at'], 'email_folder_outdated_at_idx');
    }

    /**
     * Create oro_email_user table
     */
    private function createOroEmailUserTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
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
    private function createOroEmailThreadTable(Schema $schema): void
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
    private function createOroEmailBodyTable(Schema $schema): void
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
    private function createOroEmailMailboxTable(Schema $schema, QueryBag $queries): void
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
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email']);
        $table->addUniqueIndex(['label']);
        $table->addUniqueIndex(['process_settings_id']);
        $table->addUniqueIndex(['origin_id']);

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_mailbox_email_ci ON oro_email_mailbox (LOWER(email))'
            ));
        }
    }

    /**
     * Create oro_email_mailbox_process table
     */
    private function createOroEmailMailboxProcessTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_process');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_email table
     */
    private function createOroEmailTable(Schema $schema): void
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['sent'], 'idx_sent');
        $table->addIndex(['is_head'], 'oro_email_is_head');
        $table->addUniqueIndex(['email_body_id']);
    }

    /**
     * Create oro_email_auto_response_rule table
     */
    private function createOroEmailAutoResponseRuleTable(Schema $schema): void
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
    private function createOroEmailTemplateLocalizedTable(Schema $schema): void
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
     * Create oro_email_address_visibility table
     */
    private function createOroEmailAddressVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_address_visibility');
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => true]);
        $table->addColumn('is_visible', 'boolean');
        $table->setPrimaryKey(['email', 'organization_id']);
    }

    /**
     * Add oro_email_address foreign keys.
     */
    private function addOroEmailAddressForeignKeys(Schema $schema): void
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
    private function addOroEmailAttachmentForeignKeys(Schema $schema): void
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
    private function addOroEmailRecipientForeignKeys(Schema $schema): void
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
    private function addOroEmailTemplateForeignKeys(Schema $schema): void
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
    private function addOroEmailAttachmentContentForeignKeys(Schema $schema): void
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
    private function addOroEmailFolderForeignKeys(Schema $schema): void
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
    private function addOroEmailUserForeignKeys(Schema $schema): void
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
    }

    /**
     * Add oro_email_thread foreign keys.
     */
    private function addOroEmailThreadForeignKeys(Schema $schema): void
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
    private function addOroEmailMailboxForeignKeys(Schema $schema): void
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
    private function addOroEmailForeignKeys(Schema $schema): void
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
    private function addOroEmailAutoResponseRuleForeignKeys(Schema $schema): void
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
    private function addOroEmailTemplateLocalizedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template_localized');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_address_visibility foreign keys.
     */
    private function addOroEmailAddressVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_address_visibility');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
