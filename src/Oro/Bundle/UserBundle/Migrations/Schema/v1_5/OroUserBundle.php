<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery('ALTER TABLE oro_user_access_group DROP FOREIGN KEY FK_EC003EF3FE54D947;');
        $queries->addPreQuery('ALTER TABLE oro_notification_recip_group DROP FOREIGN KEY FK_14F109F1FE54D947;');
        $queries->addPreQuery('ALTER TABLE oro_user_access_group_role DROP FOREIGN KEY FK_E7E7E38EFE54D947;');
        $queries->addPreQuery('ALTER TABLE oro_user_access_group_role DROP FOREIGN KEY FK_E7E7E38ED60322AC;');
        $queries->addPreQuery('ALTER TABLE oro_user_access_role DROP FOREIGN KEY FK_290571BED60322AC;');
        $queries->addPreQuery('ALTER TABLE oro_user DROP FOREIGN KEY fk_oro_user_status_id;');

        $table = $schema->getTable('oro_access_group');
        $table->getColumn('id')->setType(Type::getType('integer'));

        $table = $schema->getTable('oro_access_role');
        $table->getColumn('id')->setType(Type::getType('integer'));

        $table = $schema->getTable('oro_user');
        $table->getColumn('status_id')->setType(Type::getType('integer'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user_status'),
            ['status_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user_access_group');
        $table->getColumn('group_id')->setType(Type::getType('integer'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user_access_group_role');
        $table->getColumn('group_id')->setType(Type::getType('integer'));
        $table->getColumn('role_id')->setType(Type::getType('integer'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user_access_role');
        $table->getColumn('role_id')->setType(Type::getType('integer'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_access_role'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user_email');
        $table->getColumn('id')->setType(Type::getType('integer'));

        $table = $schema->getTable('oro_user_status');
        $table->getColumn('id')->setType(Type::getType('integer'));
    }
}
