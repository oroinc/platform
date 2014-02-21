<?php

namespace Oro\Bundle\UserBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroUserBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_access_group **/
        $table = $schema->createTable('oro_access_group');
        $table->addColumn('id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('business_unit_owner_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_FEF9EDB75E237E06');
        $table->addIndex(['business_unit_owner_id'], 'IDX_FEF9EDB759294170', []);
        /** End of generate table oro_access_group **/

        /** Generate table oro_access_role **/
        $table = $schema->createTable('oro_access_role');
        $table->addColumn('id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('business_unit_owner_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('role', 'string', ['default' => null, 'notnull' => true, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => true, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role'], 'UNIQ_673F65E757698A6A');
        $table->addIndex(['business_unit_owner_id'], 'IDX_673F65E759294170', []);
        /** End of generate table oro_access_role **/

        /** Generate table oro_session **/
        $table = $schema->createTable('oro_session');
        $table->addColumn('id', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('sess_data', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('sess_time', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_session **/

        /** Generate table oro_user **/
        $table = $schema->createTable('oro_user');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('imap_configuration_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('business_unit_owner_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('status_id', 'smallint', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('username', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('email', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name_prefix', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('first_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('middle_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('last_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name_suffix', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('birthday', 'date', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('image', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('enabled', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('salt', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('password', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('confirmation_token', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('password_requested', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('last_login', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('login_count', 'integer', ['default' => '0', 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('createdAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updatedAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['username'], 'UNIQ_F82840BCF85E0677');
        $table->addUniqueIndex(['email'], 'UNIQ_F82840BCE7927C74');
        $table->addUniqueIndex(['status_id'], 'UNIQ_F82840BC6BF700BD');
        $table->addUniqueIndex(['imap_configuration_id'], 'UNIQ_F82840BC678BF607');
        $table->addIndex(['business_unit_owner_id'], 'IDX_F82840BC59294170', []);
        /** End of generate table oro_user **/

        /** Generate table oro_user_access_group **/
        $table = $schema->createTable('oro_user_access_group');
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('group_id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['user_id', 'group_id']);
        $table->addIndex(['user_id'], 'IDX_EC003EF3A76ED395', []);
        $table->addIndex(['group_id'], 'IDX_EC003EF3FE54D947', []);
        /** End of generate table oro_user_access_group **/

        /** Generate table oro_user_access_group_role **/
        $table = $schema->createTable('oro_user_access_group_role');
        $table->addColumn('group_id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('role_id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['group_id', 'role_id']);
        $table->addIndex(['group_id'], 'IDX_E7E7E38EFE54D947', []);
        $table->addIndex(['role_id'], 'IDX_E7E7E38ED60322AC', []);
        /** End of generate table oro_user_access_group_role **/

        /** Generate table oro_user_access_role **/
        $table = $schema->createTable('oro_user_access_role');
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('role_id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['user_id', 'role_id']);
        $table->addIndex(['user_id'], 'IDX_290571BEA76ED395', []);
        $table->addIndex(['role_id'], 'IDX_290571BED60322AC', []);
        /** End of generate table oro_user_access_role **/

        /** Generate table oro_user_api **/
        $table = $schema->createTable('oro_user_api');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('api_key', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['api_key'], 'UNIQ_296B6993C912ED9D');
        $table->addUniqueIndex(['user_id'], 'UNIQ_296B6993A76ED395');
        /** End of generate table oro_user_api **/

        /** Generate table oro_user_business_unit **/
        $table = $schema->createTable('oro_user_business_unit');
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('business_unit_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['user_id', 'business_unit_id']);
        $table->addIndex(['user_id'], 'IDX_B190CE8FA76ED395', []);
        $table->addIndex(['business_unit_id'], 'IDX_B190CE8FA58ECB40', []);
        /** End of generate table oro_user_business_unit **/

        /** Generate table oro_user_email **/
        $table = $schema->createTable('oro_user_email');
        $table->addColumn('id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('email', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_8600BE16A76ED395', []);
        /** End of generate table oro_user_email **/

        /** Generate table oro_user_status **/
        $table = $schema->createTable('oro_user_status');
        $table->addColumn('id', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('user_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('status', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_D8DDF7AAA76ED395', []);
        /** End of generate table oro_user_status **/


        /** Generate foreign keys for table oro_access_group **/
        $table = $schema->getTable('oro_access_group');
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_access_group **/

        /** Generate foreign keys for table oro_access_role **/
        $table = $schema->getTable('oro_access_role');
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_access_role **/

        /** Generate foreign keys for table oro_user **/
        $table = $schema->getTable('oro_user');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_origin'), ['imap_configuration_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_user_status'), ['status_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user **/

        /** Generate foreign keys for table oro_user_access_group **/
        $table = $schema->getTable('oro_user_access_group');
        $table->addForeignKeyConstraint($schema->getTable('oro_access_group'), ['group_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_access_group **/

        /** Generate foreign keys for table oro_user_access_group_role **/
        $table = $schema->getTable('oro_user_access_group_role');
        $table->addForeignKeyConstraint($schema->getTable('oro_access_role'), ['role_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_access_group'), ['group_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_access_group_role **/

        /** Generate foreign keys for table oro_user_access_role **/
        $table = $schema->getTable('oro_user_access_role');
        $table->addForeignKeyConstraint($schema->getTable('oro_access_role'), ['role_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_access_role **/

        /** Generate foreign keys for table oro_user_api **/
        $table = $schema->getTable('oro_user_api');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_api **/

        /** Generate foreign keys for table oro_user_business_unit **/
        $table = $schema->getTable('oro_user_business_unit');
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_business_unit **/

        /** Generate foreign keys for table oro_user_email **/
        $table = $schema->getTable('oro_user_email');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_email **/

        /** Generate foreign keys for table oro_user_status **/
        $table = $schema->getTable('oro_user_status');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['user_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_user_status **/

        /** Add user as owner to oro_email_address table **/
        $table = $schema->getTable('oro_email_address');
        $table->addColumn('owner_user_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addIndex(['owner_user_id'], 'IDX_FC9DBBC52B18554A', []);

        /** Generate foreign keys for table oro_email_address **/
        $table = $schema->getTable('oro_email_address');
        $table->addForeignKeyConstraint($schema->getTable('oro_user'), ['owner_user_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_email_address **/

        // @codingStandardsIgnoreEnd
    }
}
