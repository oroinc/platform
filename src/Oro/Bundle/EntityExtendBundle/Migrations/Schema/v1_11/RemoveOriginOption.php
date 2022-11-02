<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes "origin" option from entity and field configs.
 */
class RemoveOriginOption implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new RemoveOriginOptionQuery());
    }
}
