<?php

namespace Oro\Bundle\DataGridBundle\Extension\Columns;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;

/**
 * Updates datagrid metadata object with:
 * - initial columns state - as per datagrid columns configuration;
 * - columns state - as per current state based on columns configuration, grid view settings and datagrid parameters;
 * - updates metadata columns with current `order` and `renderable` values.
 */
class ColumnsExtension extends AbstractExtension
{
    public const MINIFIED_COLUMNS_PARAM = 'c';
    public const COLUMNS_PARAM = '_columns';

    /** @var DatagridStateProviderInterface|ColumnsStateProvider DatagridStateProviderInterface */
    private $columnsStateProvider;

    public function __construct(DatagridStateProviderInterface $columnsStateProvider)
    {
        $this->columnsStateProvider = $columnsStateProvider;
    }

    /**
     * Should be applied after FormatterExtension.
     *
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $datagridConfiguration)
    {
        if (!parent::isApplicable($datagridConfiguration)) {
            return false;
        }

        $columnsConfig = $datagridConfiguration->offsetGetOr(Configuration::COLUMNS_KEY, []);

        return count($columnsConfig) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $datagridConfiguration, MetadataObject $metadata)
    {
        $datagridParameters = $this->getParameters();

        $columnsState = $this->columnsStateProvider->getState($datagridConfiguration, $datagridParameters);
        $this->setColumnsState($metadata, $columnsState);
        $this->updateMetadataVisibility($metadata);
        $this->updateMetadataColumns($metadata, $columnsState);

        $defaultColumnsState = $this->columnsStateProvider->getDefaultState($datagridConfiguration);
        $this->setInitialColumnsState($metadata, $defaultColumnsState);
        $this->updateMetadataDefaultGridView($metadata, $defaultColumnsState);
    }

    private function setInitialColumnsState(MetadataObject $metadata, array $columnsState): void
    {
        $metadata->offsetAddToArray('initialState', ['columns' => $columnsState]);
    }

    private function updateMetadataDefaultGridView(MetadataObject $metadata, array $columnsState): void
    {
        $defaultGridViewKey = array_search(
            GridViewsExtension::DEFAULT_VIEW_ID,
            array_column($metadata->offsetGetByPath('[gridViews][views]', []), 'name'),
            false
        );

        if ($defaultGridViewKey !== false) {
            $metadata->offsetSetByPath(
                sprintf('[gridViews][views][%s][%s]', $defaultGridViewKey, 'columns'),
                $columnsState
            );
        }
    }

    private function setColumnsState(MetadataObject $metadata, array $columnsState): void
    {
        $metadata->offsetAddToArray('state', ['columns' => $columnsState]);
    }

    private function updateMetadataColumns(MetadataObject $metadata, array $columnsState): void
    {
        $columns = $metadata->offsetGetOr('columns', []);
        foreach ($columns as $index => $columnMetadata) {
            $columnName = $columnMetadata['name'] ?? null;
            if ($columnName === null) {
                continue;
            }

            $columnState = $columnsState[$columnName] ?? null;
            if ($columnState === null) {
                continue;
            }

            foreach ([ColumnsStateProvider::ORDER_FIELD_NAME, ColumnsStateProvider::RENDER_FIELD_NAME] as $configKey) {
                $metadata->offsetSetByPath(
                    sprintf('[%s][%s][%s]', 'columns', $index, $configKey),
                    $columnState[$configKey]
                );
            }
        }
    }

    private function updateMetadataVisibility(MetadataObject $metadata): void
    {
        $columns = $metadata->offsetGetOr(Configuration::COLUMNS_KEY, []);
        $columns = array_filter(array_map(function ($columnData) {
            if ($columnData[PropertyInterface::DISABLED_KEY] ?? false) {
                return null;
            }

            return $columnData;
        }, $columns));

        $metadata->offsetSet(Configuration::COLUMNS_KEY, $columns);
    }
}
