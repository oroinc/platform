<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\User;

class RemoveUserStatus implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_user_status')) {
            $schema->getTable('oro_user')->dropColumn('status_id');
            $schema->dropTable('oro_user_status');
        }

        $queries->addQuery(new RemoveFieldQuery(User::class, 'currentStatus'));
        $queries->addQuery(new RemoveFieldQuery(User::class, 'statuses'));
    }
}
