<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_user');
        if (!$table->hasColumn('unsyncedFlagCount')) {
            $table->addColumn('unsyncedFlagCount', 'integer', ['default' => '0']);
        }
    }
}
