<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAdditionalFieldsToDataAudit implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_audit');

        if ($table->hasColumn('additional_fields')) {
            return;
        }

        $table->addColumn('additional_fields', 'array', ['notnull' => false]);
    }
}
