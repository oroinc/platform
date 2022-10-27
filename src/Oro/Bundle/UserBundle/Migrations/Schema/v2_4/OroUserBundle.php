<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Handles all migrations logic executed during an update
 */
class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user_impersonation');
        if (!$table->hasIndex('oro_user_imp_ip')) {
            $table->addIndex(['ip_address'], 'oro_user_imp_ip');
        }
    }
}
