<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds comment to data field of the oro_integration_channel_status table
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
        $table = $schema->getTable('oro_integration_channel_status');
        $table->getColumn('data')
            ->setComment('(DC2Type:json_array)');
    }
}
