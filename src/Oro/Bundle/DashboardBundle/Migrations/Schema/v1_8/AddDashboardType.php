<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Query\OutdatedEnumDataValue;
use Oro\Bundle\EntityExtendBundle\Migration\Query\OutdatedInsertEnumValuesQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add dashboard_type enum field to the oro_dashboard table and adds widgets default dashboard type.
 */
class AddDashboardType implements Migration, OutdatedExtendExtensionAwareInterface
{
    use OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $enumTable = $this->outdatedExtendExtension->addOutdatedEnumField(
            $schema,
            $schema->getTable('oro_dashboard'),
            'dashboard_type',
            'dashboard_type',
            false,
            false,
            [
                'extend'    => ['owner' => ExtendScope::OWNER_SYSTEM],
                'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE, 'show_filter' => false],
                'form'      => ['is_enabled' => false],
                'view'      => ['is_displayable' => false],
                'merge'     => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        );

        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', ['widgets']);
        $enumTable->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new OutdatedInsertEnumValuesQuery($this->outdatedExtendExtension, 'dashboard_type', [
            new OutdatedEnumDataValue('widgets', 'Widgets', 1, true)
        ]));
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_dashboard SET dashboard_type_id = :default_type',
            ['default_type' => 'widgets'],
            ['default_type' => Types::STRING]
        ));
    }
}
