<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAffectedEntitiesToAsyncOperationTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_api_async_operation');
        if ($table->hasColumn('affected_entities')) {
            return;
        }

        $table->addColumn('affected_entities', 'json', ['comment' => '(DC2Type:json)', 'notnull' => false]);
    }
}
