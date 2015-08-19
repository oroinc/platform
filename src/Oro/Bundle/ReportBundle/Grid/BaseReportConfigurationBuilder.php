<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Entity\Report;

class BaseReportConfigurationBuilder extends DatagridConfigurationBuilder
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return (strpos($gridName, Report::GRID_PREFIX) === 0);
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

        $fromPart = $configuration->offsetGetByPath('[source][query][from]');

        $entityAlias = null;
        $doctrineMetadata = $this->doctrine->getManagerForClass($className)
            ->getClassMetadata($className);
        $identifiers = $doctrineMetadata->getIdentifier();
        $primaryKey = array_shift($identifiers);

        foreach ($fromPart as $piece) {
            if ($piece['table'] == $className) {
                $entityAlias = $piece['alias'];
                break;
            }
        }

        if (!$entityAlias || !$primaryKey || count($identifiers) > 1 || !$this->isActionSupported($primaryKey)) {
            return $configuration;
        }

        $viewAction = [
            'view' => [
                'type'         => 'navigate',
                'label'        => 'oro.report.datagrid.row.action.view',
                'acl_resource' => 'VIEW;entity:' . $className,
                'icon'         => 'eye-open',
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

        $configuration->offsetAddToArrayByPath(
            '[source][query][select]',
            ["{$entityAlias}.{$primaryKey}"]
        );
        $configuration->offsetAddToArrayByPath('[properties]', $properties);
        $configuration->offsetAddToArrayByPath('[actions]', $viewAction);

        return $configuration;
    }

    /**
     * @param string $primaryKey
     * @return bool
     */
    protected function isActionSupported($primaryKey)
    {
        $definition = json_decode($this->source->getDefinition(), true);

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
