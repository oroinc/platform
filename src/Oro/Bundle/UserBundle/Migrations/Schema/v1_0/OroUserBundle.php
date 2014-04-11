<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroAccessGroupTable($schema);
        self::oroAccessRoleTable($schema);
        self::oroSessionTable($schema);
        self::oroUserTable($schema);
        self::oroUserAccessGroupTable($schema);
        self::oroUserAccessGroupRoleTable($schema);
        self::oroUserAccessRoleTable($schema);
        self::oroUserApiTable($schema);
        self::oroUserBusinessUnitTable($schema);
        self::oroUserEmailTable($schema);
        self::oroUserStatusTable($schema);

        self::oroAccessGroupForeignKeys($schema);
        self::oroAccessRoleForeignKeys($schema);
        self::oroUserForeignKeys($schema);
        self::oroUserAccessGroupForeignKeys($schema);
        self::oroUserAccessGroupRoleForeignKeys($schema);
        self::oroUserAccessRoleForeignKeys($schema);
        self::oroUserApiForeignKeys($schema);
        self::oroUserBusinessUnitForeignKeys($schema);
        self::oroUserEmailForeignKeys($schema);
        self::oroUserStatusForeignKeys($schema);

        self::addOwnerToOroEmailAddress($schema);
    }

    /**
     * Generate table oro_access_group
     *
     * @param Schema $schema
     */
    public static function oroAccessGroupTable(Schema $schema)
    {
        /** Generate table oro_access_group **/
        $table = $schema->createTable('oro_access_group');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_FEF9EDB75E237E06');
        $table->addIndex(['business_unit_owner_id'], 'IDX_FEF9EDB759294170', []);
        /** End of generate table oro_access_group **/
    }

    /**
     * Generate table oro_access_role
     *
     * @param Schema $schema
     */
    public static function oroAccessRoleTable(Schema $schema)
    {
        /** Generate table oro_access_role **/
        $table = $schema->createTable('oro_access_role');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('role', 'string', ['length' => 30]);
        $table->addColumn('label', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role'], 'UNIQ_673F65E757698A6A');
        $table->addIndex(['business_unit_owner_id'], 'IDX_673F65E759294170', []);
        /** End of generate table oro_access_role **/
    }

    /**
     * Generate table oro_session
     *
     * @param Schema $schema
     */
    public static function oroSessionTable(Schema $schema)
    {
        /** Generate table oro_session **/
        $table = $schema->createTable('oro_session');
        $table->addColumn('id', 'string', ['length' => 255]);
        $table->addColumn('sess_data', 'text', []);
        $table->addColumn('sess_time', 'integer', []);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_session **/
    }

    /**
     * Generate table oro_user
     *
     * @param Schema $schema
     * @param bool   $addImapConfigurationId
     */
    public static function oroUserTable(Schema $schema, $addImapConfigurationId = true)
    {
        /** Generate table oro_user **/
        $table = $schema->createTable('oro_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        if ($addImapConfigurationId) {
            $table->addColumn('imap_configuration_id', 'integer', ['notnull' => false]);
        }
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('status_id', 'smallint', ['notnull' => false]);
        $table->addColumn('username', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('birthday', 'date', ['notnull' => false]);
        $table->addColumn('image', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('enabled', 'boolean', []);
        $table->addColumn('salt', 'string', ['length' => 255]);
        $table->addColumn('password', 'string', ['length' => 255]);
        $table->addColumn('confirmation_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('password_requested', 'datetime', ['notnull' => false]);
        $table->addColumn('last_login', 'datetime', ['notnull' => false]);
        $table->addColumn('login_count', 'integer', ['default' => '0', 'unsigned' => true]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['username'], 'UNIQ_F82840BCF85E0677');
        $table->addUniqueIndex(['email'], 'UNIQ_F82840BCE7927C74');
        $table->addUniqueIndex(['status_id'], 'UNIQ_F82840BC6BF700BD');
        $table->addUniqueIndex(['imap_configuration_id'], 'UNIQ_F82840BC678BF607');
        $table->addIndex(['business_unit_owner_id'], 'IDX_F82840BC59294170', []);
        /** End of generate table oro_user **/
    }

    /**
     * Generate table oro_user_access_group
     *
     * @param Schema $schema
     */
    public static function oroUserAccessGroupTable(Schema $schema)
    {
        /** Generate table oro_user_access_group **/
        $table = $schema->createTable('oro_user_access_group');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('group_id', 'smallint', []);
        $table->setPrimaryKey(['user_id', 'group_id']);
        $table->addIndex(['user_id'], 'IDX_EC003EF3A76ED395', []);
        $table->addIndex(['group_id'], 'IDX_EC003EF3FE54D947', []);
        /** End of generate table oro_user_access_group **/
    }

    /**
     * Generate table oro_user_access_group_role
     *
     * @param Schema $schema
     */
    public static function oroUserAccessGroupRoleTable(Schema $schema)
    {
        /** Generate table oro_user_access_group_role **/
        $table = $schema->createTable('oro_user_access_group_role');
        $table->addColumn('group_id', 'smallint', []);
        $table->addColumn('role_id', 'smallint', []);
        $table->setPrimaryKey(['group_id', 'role_id']);
        $table->addIndex(['group_id'], 'IDX_E7E7E38EFE54D947', []);
        $table->addIndex(['role_id'], 'IDX_E7E7E38ED60322AC', []);
        /** End of generate table oro_user_access_group_role **/
    }

    /**
     * Generate table oro_user_access_role
     *
     * @param Schema $schema
     */
    public static function oroUserAccessRoleTable(Schema $schema)
    {
        /** Generate table oro_user_access_role **/
        $table = $schema->createTable('oro_user_access_role');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('role_id', 'smallint', []);
        $table->setPrimaryKey(['user_id', 'role_id']);
        $table->addIndex(['user_id'], 'IDX_290571BEA76ED395', []);
        $table->addIndex(['role_id'], 'IDX_290571BED60322AC', []);
        /** End of generate table oro_user_access_role **/
    }

    /**
     * Generate table oro_user_api
     *
     * @param Schema $schema
     */
    public static function oroUserApiTable(Schema $schema)
    {
        /** Generate table oro_user_api **/
        $table = $schema->createTable('oro_user_api');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('api_key', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['api_key'], 'UNIQ_296B6993C912ED9D');
        $table->addUniqueIndex(['user_id'], 'UNIQ_296B6993A76ED395');
        /** End of generate table oro_user_api **/
    }

    /**
     * Generate table oro_user_business_unit
     *
     * @param Schema $schema
     */
    public static function oroUserBusinessUnitTable(Schema $schema)
    {
        /** Generate table oro_user_business_unit **/
        $table = $schema->createTable('oro_user_business_unit');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('business_unit_id', 'integer', []);
        $table->setPrimaryKey(['user_id', 'business_unit_id']);
        $table->addIndex(['user_id'], 'IDX_B190CE8FA76ED395', []);
        $table->addIndex(['business_unit_id'], 'IDX_B190CE8FA58ECB40', []);
        /** End of generate table oro_user_business_unit **/
    }

    /**
     * Generate table oro_user_email
     *
     * @param Schema $schema
     */
    public static function oroUserEmailTable(Schema $schema)
    {
        /** Generate table oro_user_email **/
        $table = $schema->createTable('oro_user_email');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_8600BE16A76ED395', []);
        /** End of generate table oro_user_email **/
    }

    /**
     * Generate table oro_user_status
     *
     * @param Schema $schema
     */
    public static function oroUserStatusTable(Schema $schema)
    {
        /** Generate table oro_user_status **/
        $table = $schema->createTable('oro_user_status');
        $table->addColumn('id', 'smallint', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_D8DDF7AAA76ED395', []);
        /** End of generate table oro_user_status **/
    }

    /**
     * Generate foreign keys for table oro_access_group
     *
     * @param Schema $schema
     */
    public static function oroAccessGroupForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_access_group **/
        $table = $schema->getTable('oro_access_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_access_group **/
    }

    /**
     * Generate foreign keys for table oro_access_role
     *
     * @param Schema $schema
     */
    public static function oroAccessRoleForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_access_role **/
        $table = $schema->getTable('oro_access_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_access_role **/
    }

    /**
     * Generate foreign keys for table oro_user
     *
     * @param Schema $schema
     * @param bool   $addImapConfigurationId
     */
    public static function oroUserForeignKeys(Schema $schema, $addImapConfigurationId = true)
    {
        /** Generate foreign keys for table oro_user **/
        $table = $schema->getTable('oro_user');
        if ($addImapConfigurationId) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_email_origin'),
                ['imap_configuration_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );
        }
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user_status'),
            ['status_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user **/
    }

    /**
     * Generate foreign keys for table oro_user_access_group
     *
     * @param Schema $schema
     */
    public static function oroUserAccessGroupForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_access_group **/
        $table = $schema->getTable('oro_user_access_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_access_group **/
    }

    /**
     * Generate foreign keys for table oro_user_access_group_role
     *
     * @param Schema $schema
     */
    public static function oroUserAccessGroupRoleForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_access_group_role **/
        $table = $schema->getTable('oro_user_access_group_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_access_group_role **/
    }

    /**
     * Generate foreign keys for table oro_user_access_role
     *
     * @param Schema $schema
     */
    public static function oroUserAccessRoleForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_access_role **/
        $table = $schema->getTable('oro_user_access_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_access_role **/
    }

    /**
     * Generate foreign keys for table oro_user_api
     *
     * @param Schema $schema
     */
    public static function oroUserApiForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_api **/
        $table = $schema->getTable('oro_user_api');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_api **/
    }

    /**
     * Generate foreign keys for table oro_user_business_unit
     *
     * @param Schema $schema
     */
    public static function oroUserBusinessUnitForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_business_unit **/
        $table = $schema->getTable('oro_user_business_unit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_business_unit **/
    }

    /**
     * Generate foreign keys for table oro_user_email
     *
     * @param Schema $schema
     */
    public static function oroUserEmailForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_email **/
        $table = $schema->getTable('oro_user_email');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_email **/
    }

    /**
     * Generate foreign keys for table oro_user_status
     *
     * @param Schema $schema
     */
    public static function oroUserStatusForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_status **/
        $table = $schema->getTable('oro_user_status');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_status **/
    }

    /**
     * Add owner to table oro_email_address
     *
     * @param Schema $schema
     */
    public static function addOwnerToOroEmailAddress(Schema $schema)
    {
        /** Add user as owner to oro_email_address table **/
        $table = $schema->getTable('oro_email_address');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_FC9DBBC52B18554A', []);

        /** Generate foreign keys for table oro_email_address **/
        $table = $schema->getTable('oro_email_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_email_address **/
    }
}
