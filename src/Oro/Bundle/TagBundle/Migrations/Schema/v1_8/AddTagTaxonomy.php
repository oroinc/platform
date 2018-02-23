<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddTagTaxonomy implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_tag_tag **/
        $table = $schema->createTable('oro_tag_taxonomy');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('background_color', 'string', ['length' => 7, 'notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name', 'organization_id'], 'tag_taxonomy_name_organization_idx');
        $table->addIndex(['user_owner_id'], 'tag_taxonomy_user_owner_idx', []);
        /** End of generate table oro_tag_tag **/

        $tagTable = $schema->getTable('oro_tag_tag');
        $tagTable->addColumn('taxonomy_id', 'integer', ['notnull' => false]);

        $tagTable->addForeignKeyConstraint(
            $table,
            ['taxonomy_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
