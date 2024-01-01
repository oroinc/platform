<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Query\EnumDataValue;
use Oro\Bundle\EntityExtendBundle\Migration\Query\InsertEnumValuesQuery;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OroUserBundleInstaller implements
    Installation,
    DatabasePlatformAwareInterface,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use AttachmentExtensionAwareTrait;
    use ExtendExtensionAwareTrait;
    use ActivityExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v2_10';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroUserEmailTable($schema, $queries);
        $this->createOroUserApiTable($schema);
        $this->createOroUserTable($schema);
        $this->createOroUserOrganizationTable($schema);
        $this->createOroUserImpersonationTable($schema);
        $this->createOroUserAccessRoleTable($schema);
        $this->createOroUserAccessGroupTable($schema);
        $this->createOroUserBusinessUnitTable($schema);
        $this->createOroAccessGroupTable($schema);
        $this->createOroUserAccessGroupRoleTable($schema);
        $this->createOroAccessRoleTable($schema);
        $this->createOroUserLoginAttemptsTable($schema);
        $this->createOroEmailMailboxUsersTable($schema);
        $this->createOroEmailMailboxRolesTable($schema);
        $this->createOroEmailUserFoldersTable($schema);

        /** Foreign keys generation **/
        $this->addOroUserEmailForeignKeys($schema);
        $this->addOroUserApiForeignKeys($schema);
        $this->addOroUserForeignKeys($schema);
        $this->addOroUserOrganizationForeignKeys($schema);
        $this->addOroUserImpersonationForeignKeys($schema);
        $this->addOroUserAccessRoleForeignKeys($schema);
        $this->addOroUserAccessGroupForeignKeys($schema);
        $this->addOroUserBusinessUnitForeignKeys($schema);
        $this->addOroAccessGroupForeignKeys($schema);
        $this->addOroUserAccessGroupRoleForeignKeys($schema);
        $this->addOroUserLoginAttemptsForeignKeys($schema);
        $this->addOroEmailMailboxUsersForeignKeys($schema);
        $this->addOroEmailMailboxRolesForeignKeys($schema);
        $this->addOroEmailUserFoldersForeignKeys($schema);

        $this->addOwnerToAttachmentFileTable($schema);
        $this->addOwnerToAttachmentTable($schema);
        $this->addOwnerToEmailTemplateTable($schema);
        $this->addOwnerToEmailAddressTable($schema);
        $this->addOwnerToEmailOriginTable($schema);
        $this->addOwnerToEmailUserTable($schema);

        $this->activityExtension->addActivityAssociation($schema, 'oro_email', 'oro_user', true);

        $this->updateOroEmailUserTable($schema);

        $this->addOroUserAuthStatusField($schema, $queries);
        $this->addOroUserRelationToScope($schema);
    }

    /**
     * Create oro_user_email table
     */
    private function createOroUserEmailTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_user_email');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255, 'precision' => 0]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_8600BE16A76ED395');
        $table->addIndex(['email'], 'idx_user_email');

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_user_email_ci ON oro_user_email (LOWER(email))'
            ));
        }
    }

    /**
     * Create oro_user_api table
     */
    private function createOroUserApiTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_api');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('api_key', 'crypted_string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['api_key'], 'UNIQ_296B6993C912ED9D');
        $table->addIndex(['user_id'], 'IDX_296B6993A76ED395');
        $table->addIndex(['organization_id'], 'IDX_296B699332C8A3DE');
    }

    /**
     * Create oro_user table
     */
    private function createOroUserTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('username', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('username_lowercase', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('email_lowercase', 'string', ['length' => 255]);
        $table->addColumn(
            'phone',
            'string',
            [
                'length'      => 255,
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => true]
                ]
            ]
        );
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('birthday', 'date', ['notnull' => false, 'precision' => 0]);
        $table->addColumn('enabled', 'boolean', ['precision' => 0]);
        $table->addColumn('salt', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('password', 'string', ['length' => 255, 'precision' => 0]);
        $table->addColumn('confirmation_token', 'string', ['notnull' => false, 'length' => 255, 'precision' => 0]);
        $table->addColumn('password_requested', 'datetime', ['notnull' => false, 'precision' => 0]);
        $table->addColumn('password_changed', 'datetime', ['notnull' => false]);
        $table->addColumn('last_login', 'datetime', ['notnull' => false, 'precision' => 0]);
        $table->addColumn('login_count', 'integer', ['default' => '0', 'precision' => 0, 'unsigned' => true]);
        $table->addColumn('createdAt', 'datetime', ['precision' => 0]);
        $table->addColumn('updatedAt', 'datetime', ['precision' => 0]);
        $table->addColumn(
            'title',
            'string',
            [
                'length'      => 255,
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
                ]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['username'], 'UNIQ_F82840BCF85E0677');
        $table->addUniqueIndex(['email'], 'UNIQ_F82840BCE7927C74');
        $table->addIndex(['username_lowercase'], 'idx_oro_user_username_lowercase');
        $table->addIndex(['email_lowercase'], 'idx_oro_user_email_lowercase');
        $table->addIndex(['phone'], 'oro_idx_user_phone');
        $table->addIndex(['business_unit_owner_id'], 'IDX_F82840BC59294170');
        $table->addIndex(['organization_id'], 'IDX_F82840BC32C8A3DE');
        $table->addIndex(['first_name', 'last_name'], 'user_first_name_last_name_idx');

        $this->attachmentExtension->addImageRelation($schema, 'oro_user', 'avatar', [], 2, 58, 58);
    }

    /**
     * Create oro_user_organization table
     */
    private function createOroUserOrganizationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_organization');
        $table->addColumn('user_id', 'integer');
        $table->addColumn('organization_id', 'integer');
        $table->setPrimaryKey(['user_id', 'organization_id']);
        $table->addIndex(['user_id'], 'IDX_A9BB6519A76ED395');
        $table->addIndex(['organization_id'], 'IDX_A9BB651932C8A3DE');
    }

    /**
     * Create oro_user_impersonation table
     */
    private function createOroUserImpersonationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_impersonation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->addColumn('token', 'string', ['length' => 255]);
        $table->addColumn('expire_at', 'datetime');
        $table->addColumn('login_at', 'datetime', ['notnull' => false]);
        $table->addColumn('notify', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('ip_address', 'string', ['length' => 255, 'nullable' => false, 'default' => '127.0.0.1']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['token'], 'token_idx');
        $table->addIndex(['ip_address'], 'oro_user_imp_ip');
    }

    /**
     * Create oro_user_access_role table
     */
    private function createOroUserAccessRoleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_access_role');
        $table->addColumn('user_id', 'integer');
        $table->addColumn('role_id', 'integer');
        $table->setPrimaryKey(['user_id', 'role_id']);
        $table->addIndex(['user_id'], 'IDX_290571BEA76ED395');
        $table->addIndex(['role_id'], 'IDX_290571BED60322AC');
    }

    /**
     * Create oro_user_access_group table
     */
    private function createOroUserAccessGroupTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_access_group');
        $table->addColumn('user_id', 'integer');
        $table->addColumn('group_id', 'integer');
        $table->setPrimaryKey(['user_id', 'group_id']);
        $table->addIndex(['user_id'], 'IDX_EC003EF3A76ED395');
        $table->addIndex(['group_id'], 'IDX_EC003EF3FE54D947');
    }

    /**
     * Create oro_user_business_unit table
     */
    private function createOroUserBusinessUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_business_unit');
        $table->addColumn('user_id', 'integer');
        $table->addColumn('business_unit_id', 'integer');
        $table->setPrimaryKey(['user_id', 'business_unit_id']);
        $table->addIndex(['user_id'], 'IDX_B190CE8FA76ED395');
        $table->addIndex(['business_unit_id'], 'IDX_B190CE8FA58ECB40');
    }

    /**
     * Create oro_access_group table
     */
    private function createOroAccessGroupTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_access_group');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 30, 'precision' => 0]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'organization_id'], 'uq_name_org_idx');
        $table->addIndex(['organization_id'], 'IDX_FEF9EDB732C8A3DE');
        $table->addIndex(['business_unit_owner_id'], 'IDX_FEF9EDB759294170');
    }

    /**
     * Create oro_user_access_group_role table
     */
    private function createOroUserAccessGroupRoleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_access_group_role');
        $table->addColumn('group_id', 'integer');
        $table->addColumn('role_id', 'integer');
        $table->setPrimaryKey(['group_id', 'role_id']);
        $table->addIndex(['group_id'], 'IDX_E7E7E38EFE54D947');
        $table->addIndex(['role_id'], 'IDX_E7E7E38ED60322AC');
    }

    /**
     * Create oro_access_role table
     */
    private function createOroAccessRoleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_access_role');
        $table->addColumn('id', 'integer', ['precision' => 0, 'autoincrement' => true]);
        $table->addColumn('role', 'string', ['length' => 30, 'precision' => 0]);
        $table->addColumn('label', 'string', ['length' => 30, 'precision' => 0]);
        $table->addColumn(
            'extend_description',
            'text',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'     => ['display' => true],
                    'dataaudit' => ['auditable' => true],
                    'form'      => ['type' => OroResizeableRichTextType::class],
                    'view'      => ['type' => 'html'],
                ]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role'], 'UNIQ_673F65E757698A6A');
    }

    private function createOroUserLoginAttemptsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_login');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('attempt_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('success', 'boolean', ['notnull' => true]);
        $table->addColumn('source', 'integer', ['notnull' => true]);
        $table->addColumn('username', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('ip', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('user_agent', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('context', 'json', ['notnull' => true, 'comment' => '(DC2Type:json)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'idx_aa4c6465a76ed395');
        $table->addIndex(['attempt_at'], 'oro_user_log_att_at_idx');
    }

    /**
     * Create oro_email_mailbox_users table
     */
    private function createOroEmailMailboxUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_users');
        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['mailbox_id', 'user_id']);
        $table->addIndex(['mailbox_id'], 'IDX_F6E5635A66EC35CC');
        $table->addIndex(['user_id'], 'IDX_F6E5635AA76ED395');
    }

    /**
     * Create oro_email_mailbox_roles table
     */
    private function createOroEmailMailboxRolesTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_mailbox_roles');
        $table->addColumn('mailbox_id', 'integer');
        $table->addColumn('role_id', 'integer');
        $table->setPrimaryKey(['mailbox_id', 'role_id']);
        $table->addIndex(['mailbox_id'], 'IDX_5458E87466EC35CC');
        $table->addIndex(['role_id'], 'IDX_5458E874D60322AC');
    }

    /**
     * Create oro_email_user_folders table
     */
    private function createOroEmailUserFoldersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_user_folders');
        $table->addColumn('email_user_id', 'integer');
        $table->addColumn('folder_id', 'integer');
        $table->setPrimaryKey(['email_user_id', 'folder_id']);
        $table->addIndex(['email_user_id'], 'IDX_201746D71AAEBB5A');
        $table->addIndex(['folder_id'], 'IDX_201746D7162CB942');
    }

    /**
     * Add oro_user_email foreign keys.
     */
    private function addOroUserEmailForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_email');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id']
        );
    }

    /**
     * Add oro_user_api foreign keys.
     */
    private function addOroUserApiForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_api');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_user foreign keys.
     */
    private function addOroUserForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_user_organization foreign keys.
     */
    private function addOroUserOrganizationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_organization');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_user_impersonation foreign keys.
     */
    private function addOroUserImpersonationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_impersonation');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_user_access_role foreign keys.
     */
    private function addOroUserAccessRoleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_access_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_user_access_group foreign keys.
     */
    private function addOroUserAccessGroupForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_access_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_user_business_unit foreign keys.
     */
    private function addOroUserBusinessUnitForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_business_unit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_access_group foreign keys.
     */
    private function addOroAccessGroupForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_access_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_user_access_group_role foreign keys.
     */
    private function addOroUserAccessGroupRoleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_access_group_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    private function addOroUserLoginAttemptsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_login');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_email_mailbox_users foreign keys.
     */
    private function addOroEmailMailboxUsersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_mailbox_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_mailbox_roles foreign keys.
     */
    private function addOroEmailMailboxRolesForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_mailbox_roles');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_user_folders foreign keys.
     */
    private function addOroEmailUserFoldersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user_folders');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_user'),
            ['email_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_attachment_file table
     */
    private function addOwnerToAttachmentFileTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_6E4CD01B9EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_attachment table
     */
    private function addOwnerToAttachmentTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_FA0FE0812B18554A');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_email_template table
     */
    private function addOwnerToEmailTemplateTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['user_owner_id'], 'IDX_E62049DE9EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_email_address table
     */
    private function addOwnerToEmailAddressTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_address');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_FC9DBBC52B18554A');
        $table = $schema->getTable('oro_email_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_email_origin table
     */
    private function addOwnerToEmailOriginTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add owner to oro_email_user table
     */
    private function addOwnerToEmailUserTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_91F5CFF632C8A3DE');
        $table->addIndex(['user_owner_id'], 'IDX_91F5CFF69EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF632C8A3DE'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_91F5CFF69EB185F9'
        );
    }

    private function addOroUserAuthStatusField(Schema $schema, QueryBag $queries): void
    {
        $enumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_user',
            'auth_status',
            'auth_status'
        );

        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', [UserManager::STATUS_ACTIVE, UserManager::STATUS_RESET]);
        $enumTable->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new InsertEnumValuesQuery($this->extendExtension, 'auth_status', [
            new EnumDataValue(UserManager::STATUS_ACTIVE, 'Active', 1, true),
            new EnumDataValue(UserManager::STATUS_RESET, 'Reset', 2)
        ]));

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_user SET auth_status_id = :default_status',
            ['default_status' => UserManager::STATUS_ACTIVE],
            ['default_status' => Types::STRING]
        ));
    }

    private function addOroUserRelationToScope(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_scope',
            'user',
            'oro_user',
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ]
        );
    }

    private function updateOroEmailUserTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addIndex(['origin_id'], 'IDX_91F5CFF656A273CC');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table->addIndex(
            ['user_owner_id', 'mailbox_owner_id', 'organization_id'],
            'user_owner_id_mailbox_owner_id_organization_id'
        );
    }
}
