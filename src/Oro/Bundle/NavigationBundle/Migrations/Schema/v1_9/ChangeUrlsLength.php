<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeUrlsLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_item');
        $table
            ->getColumn('url')
            ->setType(Type::getType(Types::STRING))
            ->setOptions(['length' => 8190, 'notnull' => true]);

        $table = $schema->getTable('oro_navigation_history');
        $table
            ->getColumn('url')
            ->setType(Type::getType(Types::STRING))
            ->setOptions(['length' => 8190, 'notnull' => true]);

        $table = $schema->getTable('oro_navigation_menu_upd');
        $table
            ->getColumn('uri')
            ->setType(Type::getType(Types::STRING))
            ->setOptions(['length' => 8190, 'notnull' => false]);
    }
}
