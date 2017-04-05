<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOwnerDescriptionColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOwnerDecriptionColumn($schema);
    }

    /**
     * Adds owner_description column
     *
     * @param Schema $schema
     */
    public static function addOwnerDecriptionColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_audit');

        $table->addColumn('owner_description', 'string', ['notnull' => false, 'length' => 255]);
    }
}
