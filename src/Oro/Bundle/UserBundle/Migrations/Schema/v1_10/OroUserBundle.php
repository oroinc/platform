<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addPasswordChangedColumn($schema);
    }

    public static function addPasswordChangedColumn(Schema $schema)
    {
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn(
            'password_changed',
            'datetime',
            [
                'notnull' => false,
            ]
        );
    }
}
