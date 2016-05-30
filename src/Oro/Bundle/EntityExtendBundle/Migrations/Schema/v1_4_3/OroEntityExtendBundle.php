<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_4_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new RemoveInvalidFieldConfigQuery());
        $queries->addQuery(new RemoveInvalidEntityConfigQuery());
    }
}
