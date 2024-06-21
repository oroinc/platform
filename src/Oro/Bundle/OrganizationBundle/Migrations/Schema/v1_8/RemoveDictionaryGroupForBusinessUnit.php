<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveDictionaryGroupForBusinessUnit implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new RemoveDictionaryGroupForBusinessUnitQuery());
    }
}
