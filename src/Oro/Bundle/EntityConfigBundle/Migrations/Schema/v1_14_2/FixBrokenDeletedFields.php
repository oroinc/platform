<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_14_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Execute deleted fields fix query.
 */
class FixBrokenDeletedFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new FixBrokenDeletedFieldsQuery());
    }
}
