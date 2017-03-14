<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateRelationsExtendConfig());
        $queries->addPostQuery(new RemoveFieldQuery('Oro\Bundle\TestFrameworkBundle\Entity\Item', 'isActive'));
    }
}
