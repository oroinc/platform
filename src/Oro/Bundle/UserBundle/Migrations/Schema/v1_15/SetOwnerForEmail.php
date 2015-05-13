<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetOwnerForEmail implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOwnerToOroEmail($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    public static function addOwnerToOroEmail(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);

        $table->addIndex(['organization_id'], 'IDX_2A30C17132C8A3DE');
        $table->addIndex(['user_owner_id'], 'IDX_2A30C1719EB185F9');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_2A30C17132C8A3DE'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_2A30C1719EB185F9'
        );
    }
}
