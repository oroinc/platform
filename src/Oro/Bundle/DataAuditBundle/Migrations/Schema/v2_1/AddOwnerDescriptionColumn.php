<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOwnerDescriptionColumn implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_audit')
            ->addColumn('owner_description', 'string', ['notnull' => false, 'length' => 255]);
    }
}
