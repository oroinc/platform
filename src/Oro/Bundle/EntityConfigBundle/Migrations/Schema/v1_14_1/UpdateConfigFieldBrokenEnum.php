<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Execute broken enums fix query.
 */
class UpdateConfigFieldBrokenEnum implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateConfigFieldBrokenEnumQuery());
    }
}
