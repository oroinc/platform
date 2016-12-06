<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDisableLogin implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user');
        $table->addColumn('login_disabled', 'boolean', ['default' => false]);
    }
}
