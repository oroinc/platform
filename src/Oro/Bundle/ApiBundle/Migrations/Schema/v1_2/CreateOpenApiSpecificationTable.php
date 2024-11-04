<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateOpenApiSpecificationTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_api_openapi_specification')) {
            return;
        }

        $table = $schema->createTable('oro_api_openapi_specification');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('status', 'string', ['length' => 8]);
        $table->addColumn('published', 'boolean');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('public_slug', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('view', 'string', ['length' => 100]);
        $table->addColumn('format', 'string', ['length' => 20]);
        $table->addColumn('entities', 'simple_array', ['comment' => '(DC2Type:simple_array)', 'notnull' => false]);
        $table->addColumn('specification', 'text', ['notnull' => false]);
        $table->addColumn('specification_created_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_9AE6DA3A32C8A3DE');
        $table->addIndex(['user_owner_id'], 'IDX_9AE6DA3A9EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
