<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * Updates the hashes of pinned pages, since the hash must contain the user ID.
 */
class UpdatePageStateHash implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                'UPDATE oro_navigation_pagestate SET page_hash = MD5(CONCAT(page_id, \'_\', user_id))'
            )
        );
    }
}
