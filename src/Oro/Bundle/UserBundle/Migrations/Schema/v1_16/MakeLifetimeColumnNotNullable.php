<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeLifetimeColumnNotNullable implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_session');
        $table->changeColumn('sess_lifetime', ['nullable' => false]);

    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
