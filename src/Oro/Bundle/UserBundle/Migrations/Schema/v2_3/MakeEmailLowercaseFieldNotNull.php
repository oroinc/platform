<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Make column not nullable after it is filled for all rows.
 */
class MakeEmailLowercaseFieldNotNull implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user');
        $table->changeColumn('email_lowercase', ['notnull' => true]);
    }
}
