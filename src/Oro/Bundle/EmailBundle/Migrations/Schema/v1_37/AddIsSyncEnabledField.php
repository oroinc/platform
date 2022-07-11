<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_37;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds the isSyncEnabled field to the oro_email_origin table.
 */
class AddIsSyncEnabledField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('is_sync_enabled', 'boolean', ['notnull' => false]);
    }
}
