<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddParentEntityClassEntityIdColumns implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_attachment_file');
        if ($table->hasColumn('parent_entity_class')) {
            return;
        }
        $table->addColumn('parent_entity_class', 'string', ['notnull' => false, 'length' => 512]);
        $table->addColumn('parent_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_entity_field_name', 'string', ['notnull' => false, 'length' => 50]);
    }
}
