<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\User;

class RemoveDisableLogin implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user');
        $table->dropColumn('login_disabled');

        $queries->addQuery(
            new RemoveFieldQuery(
                User::class,
                'loginDisabled'
            )
        );
    }
}
