<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_user')->addColumn(
            'title',
            'string',
            [
                'length'      => 255,
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
                ]
            ]
        );
    }
}
