<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_history');

        $table->addColumn('route', Type::STRING, ['length' => 128]);
        $table->addColumn('route_parameters', Type::TARRAY, ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', Type::INTEGER, ['notnull' => false]);
        $table->addIndex(['route'], 'oro_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_navigation_history_entity_id_idx');

        $queries->addPostQuery(
            sprintf('UPDATE oro_navigation_history SET route_parameters = \'%s\'', serialize([]))
        );
    }
}
