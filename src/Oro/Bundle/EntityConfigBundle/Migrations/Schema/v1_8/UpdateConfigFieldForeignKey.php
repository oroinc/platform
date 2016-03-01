<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateConfigFieldForeignKey implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_entity_config_field');
        $table->removeForeignKey('FK_63EC23F781257D5D');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
