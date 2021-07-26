<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Adds the following configuration parts to DatagridConfiguration by extending DatagridConfigurationBuilder:
 * - properties
 * - actions
 * - translation hint
 */
class BaseReportConfigurationBuilder extends DatagridConfigurationBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return str_starts_with($gridName, Report::GRID_PREFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();

        $className = $this->source->getEntity();
        $metadata = $this->configManager->getEntityMetadata($className);

        if (!$metadata || empty($metadata->routeView)) {
            return $configuration;
        }

        $entityAlias = null;
        $identifiers = $this->doctrineHelper->getEntityMetadataForClass($className)->getIdentifier();
        $primaryKey = array_shift($identifiers);
        $entityAlias = $configuration->getOrmQuery()->findRootAlias($className);

        if (!$entityAlias || !$primaryKey || count($identifiers) > 0 || !$this->isActionSupported($primaryKey)) {
            return $configuration;
        }

        $viewAction = [
            'view' => [
                'type'         => 'navigate',
                'label'        => 'oro.report.datagrid.row.action.view',
                'acl_resource' => 'VIEW;entity:' . $className,
                'icon'         => 'eye',
                'link'         => 'view_link',
                'rowAction'    => true
            ]
        ];

        $properties = [
            $primaryKey => null,
            'view_link' => [
                'type'   => 'url',
                'route'  => $metadata->routeView,
                'params' => [$primaryKey]
            ]
        ];

        $configuration->getOrmQuery()->addSelect("{$entityAlias}.{$primaryKey}");
        $configuration->offsetAddToArrayByPath('[properties]', $properties);
        $configuration->offsetAddToArrayByPath('[actions]', $viewAction);
        $configuration->offsetAddToArrayByPath('[source][hints]', ['HINT_TRANSLATABLE']);

        return $configuration;
    }

    /**
     * @param string $primaryKey
     * @return bool
     */
    protected function isActionSupported($primaryKey)
    {
        $definition = QueryDefinitionUtil::decodeDefinition($this->source->getDefinition());

        if (!empty($definition['grouping_columns'])) {
            foreach ($definition['grouping_columns'] as $column) {
                if ($column['name'] == $primaryKey) {
                    return true;
                }
            }

            return false;
        } else {
            foreach ($definition['columns'] as $column) {
                if (!empty($column['func']['group_type']) && $column['func']['group_type'] == 'aggregates') {
                    return false;
                }
            }

            return true;
        }
    }
}
