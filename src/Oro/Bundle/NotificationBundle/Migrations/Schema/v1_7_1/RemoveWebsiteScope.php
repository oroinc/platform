<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_7_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes website scope.
 */
class RemoveWebsiteScope implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new RemoveWebsiteScopeQuery()
        );
    }
}
