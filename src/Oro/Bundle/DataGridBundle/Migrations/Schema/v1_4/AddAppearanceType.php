<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAppearanceType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addAppearanceTypeTable($schema);
        
        $table = $schema->getTable('oro_grid_view');
        $table->addColumn('appearanceType', 'string', ['notnull' => false, 'length' => 32]);
        $table->addIndex(['appearanceType'], 'IDX_ORO_GRID_VIEW_APPEARANCE_TYPE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_grid_appearance_type'),
            ['appearanceType'],
            ['name'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addColumn('appearanceData', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
    }

    /**
     * @param Schema $schema
     */
    public static function addAppearanceTypeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_grid_appearance_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('icon', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
    }
}
