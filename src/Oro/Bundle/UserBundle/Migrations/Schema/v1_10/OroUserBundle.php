<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addPasswordChangedColumn($schema);
    }

    /**
     * @param Schema $schema
     */
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
