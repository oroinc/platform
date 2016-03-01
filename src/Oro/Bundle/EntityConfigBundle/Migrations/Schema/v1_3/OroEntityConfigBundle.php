<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityConfigBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_entity_config_index_value');
        $table->removeForeignKey('FK_16EF5549443707B0');
        $table->dropIndex('IDX_256E3E9B443707B0');
        $table->removeForeignKey('FK_16EF554981257D5D');
        $table->dropIndex('IDX_256E3E9B81257D5D');

        $table->addIndex(['entity_id'], 'IDX_256E3E9B81257D5D');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addIndex(['field_id'], 'IDX_256E3E9B443707B0');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
