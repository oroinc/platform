<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\User;

class RemoveUserApiEntity implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_user_api')) {
            return;
        }

        $schema->dropTable('oro_user_api');
        $queries->addQuery(new RemoveFieldQuery(User::class, 'apiKeys'));
    }
}
