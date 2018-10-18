<?php

namespace Oro\Bundle\DataGridBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

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
    public const RENDER_FIELD_NAME = 'renderable';
    public const ORDER_FIELD_NAME = 'order';

    /** @var DatagridParametersHelper */
    private $datagridParametersHelper;

    /**
     * @param GridViewManager $gridViewManager
     * @param TokenAccessorInterface $tokenAccessor
     * @param DatagridParametersHelper $datagridParametersHelper
     */
    public function __construct(
        GridViewManager $gridViewManager,
        TokenAccessorInterface $tokenAccessor,
        DatagridParametersHelper $datagridParametersHelper
    ) {
        parent::__construct($gridViewManager, $tokenAccessor);
        $this->datagridParametersHelper = $datagridParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(DatagridConfiguration $datagridConfiguration, ParameterBag $datagridParameters): array
    {
        // Fetch state from datagrid parameters.
        $state = $this->getFromParameters($datagridParameters);

        // Try to fetch state from grid view.
        if (!$state) {
            $gridView = $this->getActualGridView($datagridConfiguration, $datagridParameters);
            if ($gridView) {
                $state = $gridView->getColumnsData();
            }
        }

        return $this->sanitizeState($state, $this->getColumnsConfig($datagridConfiguration));
    }

    /**
     * {@inheritdoc}
     */
    public function getStateFromParameters(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array {
        // Fetch state from datagrid parameters.
        $state = $this->getFromParameters($datagridParameters);

        return $this->sanitizeState($state, $this->getColumnsConfig($datagridConfiguration));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultState(DatagridConfiguration $datagridConfiguration): array
    {
        return $this->sanitizeState([], $this->getColumnsConfig($datagridConfiguration));
    }

    /**
     * @param array $state
     * @param array $columnsConfig
     *
     * @return array
     */
    private function sanitizeState(array $state, array $columnsConfig): array
    {
        $state = array_intersect_key($state, $columnsConfig);

        $columnsData = array_replace_recursive($columnsConfig, $state);

        return $this->fillRenderableAndWeight($columnsData);
    }

    /**
     * {@inheritdoc}
     */
    private function getFromParameters(ParameterBag $datagridParameters): array
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
        $rawColumnsState = $this->datagridParametersHelper
            ->getFromParameters($datagridParameters, ColumnsExtension::COLUMNS_PARAM);

        // Try to fetch from minified parameters if any.
        if (!$rawColumnsState) {
            $rawColumnsState = $this->datagridParametersHelper
                ->getFromMinifiedParameters($datagridParameters, ColumnsExtension::MINIFIED_COLUMNS_PARAM);
        }

        return $rawColumnsState;
    }

    /**
     * @param array $rawColumnsState
     *
     * @return array
     */
    private function getFromNonMinifiedState(array $rawColumnsState): array
    {
        return array_filter(array_map(function ($columnData) {
            $state = [];
            if (isset($columnData[self::RENDER_FIELD_NAME])) {
                $state[self::RENDER_FIELD_NAME] = $columnData[self::RENDER_FIELD_NAME];
            }
            if (isset($columnData[self::ORDER_FIELD_NAME])) {
                $state[self::ORDER_FIELD_NAME] = $columnData[self::ORDER_FIELD_NAME];
            }

            return $state;
        }, $rawColumnsState));
    }

    /**
     * @param string $rawColumnsState
     *
     * @return array
     */
    private function getFromMinifiedState(string $rawColumnsState): array
    {
        if (!$rawColumnsState) {
            return [];
        }

        $columnsState = [];
        foreach (explode('.', $rawColumnsState) as $key => $columnState) {
            // Last char is flag indicating column state, rest part is a column name.
            $columnsState[substr($columnState, 0, -1)] = [
                self::ORDER_FIELD_NAME => (int)$key,
                self::RENDER_FIELD_NAME => substr($columnState, -1) === '1',
            ];
        }

        return $columnsState;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getColumnsConfig(DatagridConfiguration $datagridConfiguration): array
    {
        return (array)$datagridConfiguration->offsetGet(Configuration::COLUMNS_KEY);
    }

    /**
     * @param array $columnsData
     *
     * @return array
     */
    private function fillRenderableAndWeight(array $columnsData): array
    {
        $weight = 0;
        $explicitWeights = array_column($columnsData, self::ORDER_FIELD_NAME);

        return array_map(
            function (array $columnData) use (&$weight, &$explicitWeights) {
                // Fill "render" option, default is true.
                $columnState[self::RENDER_FIELD_NAME] = $columnData[self::RENDER_FIELD_NAME] ?? true;
                $columnState[self::RENDER_FIELD_NAME] = filter_var(
                    $columnState[self::RENDER_FIELD_NAME],
                    FILTER_VALIDATE_BOOLEAN
                );

                // Get "order" option if any.
                $columnState[self::ORDER_FIELD_NAME] = filter_var(
                    $columnData[self::ORDER_FIELD_NAME] ?? null,
                    FILTER_VALIDATE_INT
                );

                // Fill "order" option if it contains false.
                if ($columnState[self::ORDER_FIELD_NAME] === false) {
                    $columnState[self::ORDER_FIELD_NAME] = $weight;
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
