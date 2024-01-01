<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmbeddedFormBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_5';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroEmbeddedFormTable($schema);

        /** Foreign keys generation **/
        $this->addOroEmbeddedFormForeignKeys($schema);
    }

    private function createOroEmbeddedFormTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_embedded_form');
        $table->addColumn('id', 'string', ['length' => 255]);
        $table->addColumn('title', 'text');
        $table->addColumn('css', 'text');
        $table->addColumn('form_type', 'string', ['length' => 255]);
        $table->addColumn('success_message', 'text');
        $table->addColumn('allowed_domains', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_F7A34C17E3C61F9');
    }

    private function addOroEmbeddedFormForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_embedded_form');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
