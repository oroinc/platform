<?php

namespace Oro\Bundle\WindowsBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds comment to data field of the oro_windows_state table
 */
class AddCommentToJsonField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCommentsToJsonArrayFields($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addCommentsToJsonArrayFields(Schema $schema)
    {
        $table = $schema->getTable('oro_windows_state');
        $table->getColumn('data')
            ->setComment('(DC2Type:json_array)');
    }
}
