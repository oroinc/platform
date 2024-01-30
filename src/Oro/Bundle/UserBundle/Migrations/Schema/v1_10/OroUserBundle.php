<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_user')
            ->addColumn('password_changed', 'datetime', ['notnull' => false]);
    }
}
