<?php

namespace Oro\Bundle\DataGridBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provides request- and user-specific datagrid state for columns component.
 * Tries to fetch state from datagrid parameters, then fallbacks to state from current datagrid view, then from default
 * datagrid view, then to datagrid columns configuration.
 * State is respresented by an array with column names as key and array with the following keys as values:
 * - renderable: whether a column must be displayed on frontend
 * - order: column order (weight)
 */
class ColumnsStateProvider extends AbstractStateProvider
{
    /** @var DatagridParametersHelper */
    private $datagridParametersHelper;

    /**
     * @param ServiceLink $gridViewManagerLink
     * @param TokenAccessorInterface $tokenAccessor
     * @param DatagridParametersHelper $datagridParametersHelper
     */
    public function __construct(
        ServiceLink $gridViewManagerLink,
        TokenAccessorInterface $tokenAccessor,
        DatagridParametersHelper $datagridParametersHelper
    ) {
        parent::__construct($gridViewManagerLink, $tokenAccessor);
        $this->datagridParametersHelper = $datagridParametersHelper;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     * @param ParameterBag $datagridParameters
     *
     * @return array
     */
    public function getState(DatagridConfiguration $datagridConfiguration, ParameterBag $datagridParameters): array
    {
        // Fetch state from datagrid parameters.
        $state = $this->getStateFromParameters($datagridParameters);

        // Try to fetch state from grid view.
        if (!$state) {
            $gridView = $this->getActualGridView($datagridConfiguration, $datagridParameters);
            if ($gridView) {
                $state = $gridView->getColumnsData();
            }
        }

        return $this->sanitizeState($state, $datagridConfiguration);
    }

    /**
     * @param array $state
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function sanitizeState(array $state, DatagridConfiguration $datagridConfiguration): array
    {
        $columns = $this->getDefaultColumnsState($datagridConfiguration);
        $state = array_intersect_key($state, $columns);

        $columnsData = array_replace_recursive($columns, $state);

        return $this->fillRenderableAndWeight($columnsData);
    }

    /**
     * {@inheritdoc}
     */
    private function getStateFromParameters(ParameterBag $datagridParameters): array
    {
        $rawColumnsState = $this->getRawColumnsState($datagridParameters);
        if (!$rawColumnsState) {
            return [];
        }

        if (\is_array($rawColumnsState)) {
            $columnsState = $this->getFromNonMinifiedState($rawColumnsState);
        } else {
            $columnsState = $this->getFromMinifiedState($rawColumnsState);
        }

        return $columnsState;
    }

    /**
     * @param ParameterBag $datagridParameters
     *
     * @return array|string
     */
    private function getRawColumnsState(ParameterBag $datagridParameters)
    {
        $columnsStateString = $this->datagridParametersHelper
            ->getFromParameters($datagridParameters, ColumnsExtension::COLUMNS_PARAM);

        // Try to fetch from minified parameters if any.
        if (!$columnsStateString) {
            $columnsStateString = $this->datagridParametersHelper
                ->getFromMinifiedParameters($datagridParameters, ColumnsExtension::MINIFIED_COLUMNS_PARAM);
        }

        return $columnsStateString;
    }

    /**
     * @param array $rawColumnsState
     *
     * @return array
     */
    private function getFromNonMinifiedState(array $rawColumnsState): array
    {
        return array_filter($this->getColumnsState($rawColumnsState));
    }

    /**
     * @param string $rawColumnsState
     *
     * @return array
     */
    private function getFromMinifiedState(string $rawColumnsState): array
    {
        $columnsState = [];
        foreach (explode('.', $rawColumnsState) as $key => $columnState) {
            // Last char is flag indicating column state, rest part is a column name.
            $columnsState[substr($columnState, 0, -1)] = [
                ColumnsExtension::ORDER_FIELD_NAME => (int)$key,
                ColumnsExtension::RENDER_FIELD_NAME => substr($columnState, -1) === '1',
            ];
        }

        return $columnsState;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getDefaultColumnsState(DatagridConfiguration $datagridConfiguration): array
    {
        $columns = (array)$datagridConfiguration->offsetGet(ColumnsExtension::COLUMNS_PATH);

        return $this->getColumnsState($columns);
    }

    /**
     * @param array $columnsData
     *
     * @return array
     */
    private function getColumnsState(array $columnsData): array
    {
        return array_map(function (array $columnData) {
            $state = [];
            if (isset($columnData[ColumnsExtension::RENDER_FIELD_NAME])) {
                $state[ColumnsExtension::RENDER_FIELD_NAME] = $columnData[ColumnsExtension::RENDER_FIELD_NAME];
            }
            if (isset($columnData[ColumnsExtension::ORDER_FIELD_NAME])) {
                $state[ColumnsExtension::ORDER_FIELD_NAME] = $columnData[ColumnsExtension::ORDER_FIELD_NAME];
            }

            return $state;
        }, $columnsData);
    }

    /**
     * @param array $columnsData
     *
     * @return array
     */
    private function fillRenderableAndWeight(array $columnsData): array
    {
        $weight = 0;
        $explicitWeights = array_column($columnsData, ColumnsExtension::ORDER_FIELD_NAME);

        return array_map(
            function (array $columnData) use (&$weight, &$explicitWeights) {
                // Fill "render" option, default is true.
                $columnState[ColumnsExtension::RENDER_FIELD_NAME]
                    = $columnData[ColumnsExtension::RENDER_FIELD_NAME] ?? true;
                $columnState[ColumnsExtension::RENDER_FIELD_NAME] = filter_var(
                    $columnState[ColumnsExtension::RENDER_FIELD_NAME],
                    FILTER_VALIDATE_BOOLEAN
                );

                // Get "order" option if any.
                $columnState[ColumnsExtension::ORDER_FIELD_NAME] = filter_var(
                    $columnData[ColumnsExtension::ORDER_FIELD_NAME] ?? null,
                    FILTER_VALIDATE_INT
                );

                // Fill "order" option if it contains false.
                if ($columnState[ColumnsExtension::ORDER_FIELD_NAME] === false) {
                    $columnState[ColumnsExtension::ORDER_FIELD_NAME] = $weight;
                    $explicitWeights[] = $weight;
                    while (\in_array($weight, $explicitWeights, true)) {
                        $weight++;
                    }
                }

                return $columnState;
            },
            $columnsData
        );
    }
}
