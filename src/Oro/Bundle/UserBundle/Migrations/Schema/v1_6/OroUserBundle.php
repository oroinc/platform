<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\MigrateTypeMigration;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle extends MigrateTypeMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeType($schema, $queries, 'oro_access_group', 'id', Type::INTEGER);
        $this->changeType($schema, $queries, 'oro_access_role', 'id', Type::INTEGER);
        $this->changeType($schema, $queries, 'oro_user_email', 'id', Type::INTEGER);
        $this->changeType($schema, $queries, 'oro_user_status', 'id', Type::INTEGER);
    }
}
