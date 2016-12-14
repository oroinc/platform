<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCodeFieldToAttributeGroup($schema);
    }

    /**
     * @param Schema $schema
     */
    public function addCodeFieldToAttributeGroup(Schema $schema)
    {
        $table = $schema->getTable('oro_attribute_group');
        $table->addColumn('code', 'string', ['length' => 255]);
    }
}
