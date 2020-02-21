<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes the share permission from permissions table for community edition application.
 */
class RemoveSharePermission implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // query should be executed only for community edition application.
        if (class_exists('Oro\Bundle\PlatformProBundle\OroPlatformProBundle')) {
            return;
        }

        $queries->addPostQuery("DELETE FROM oro_security_permission WHERE name = 'SHARE'");
    }
}
