<?php

namespace Oro\Bundle\FilterBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Provider\State\AbstractStateProvider;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides request- and user-specific datagrid state for filters component.
 * Tries to fetch state from datagrid parameters, then fallbacks to state from current datagrid view, then from default
 * datagrid view, then to datagrid columns configuration.
 * State is respresented by an array with filters names as key and filter parameters array as value.
 */
class FiltersStateProvider extends AbstractStateProvider
{
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
        $state = [];
        $defaultState = $this->getDefaultFiltersState($datagridConfiguration);

        // Fetch state from datagrid parameters.
        $stateFromParameters = $this->getFromParameters($datagridParameters);
        if ($stateFromParameters) {
            $state = array_replace($defaultState, $stateFromParameters);
        }

        // Try to fetch state from grid view.
        if (!$state) {
            $gridView = $this->getActualGridView($datagridConfiguration, $datagridParameters);
            if ($gridView) {
                $state = $gridView->getFiltersData();
            }
        }

        // Fallback to default filters.
        if (!$state) {
            $state = $defaultState;
        }

        return $this->sanitizeState($state, $this->getFiltersConfig($datagridConfiguration));
    }

    /**
     * {@inheritdoc}
     */
    public function getStateFromParameters(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array {
        $defaultState = $this->getDefaultFiltersState($datagridConfiguration);

        // Fetch state from datagrid parameters.
        $stateFromParameters = $this->getFromParameters($datagridParameters);
        $state = array_replace($defaultState, $stateFromParameters);

        return $this->sanitizeState($state, $this->getFiltersConfig($datagridConfiguration));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultState(DatagridConfiguration $datagridConfiguration): array
    {
        $state = $this->getDefaultFiltersState($datagridConfiguration);

        return $this->sanitizeState($state, $this->getFiltersConfig($datagridConfiguration));
    }

    /**
     * @param array $state
     * @param array $filtersConfig
     *
     * @return array
     */
    private function sanitizeState(array $state, array $filtersConfig): array
    {
        // Remove filters which are not in datagrid configuration.
        $state = array_filter(
            $state,
            function (string $filterName) use ($filtersConfig) {
                if (isset($filtersConfig[$filterName])) {
                    return true;
                }

                // Allows filters with special key - "__{$filterName}".
                // Initially was added to AbstractFilterExtension::updateFilterStateEnabled() in scope of CRM-4760.
                if (strpos($filterName, '__') === 0) {
                    $originalFilterName = substr($filterName, 2);
                    return isset($filtersConfig[$originalFilterName]);
                }

                return false;
            },
            ARRAY_FILTER_USE_KEY
        );

        return $state;
    }

    /**
     * {@inheritdoc}
     */
    private function getFromParameters(ParameterBag $datagridParameters): array
    {
        $filtersState = (array) $this->datagridParametersHelper
            ->getFromParameters($datagridParameters, AbstractFilterExtension::FILTER_ROOT_PARAM);
        $minifiedFiltersState = (array) $this->datagridParametersHelper
            ->getFromMinifiedParameters($datagridParameters, AbstractFilterExtension::MINIFIED_FILTER_PARAM);

        return array_replace_recursive($filtersState, $minifiedFiltersState);
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getFiltersConfig(DatagridConfiguration $datagridConfiguration)
    {
        return (array)$datagridConfiguration->offsetGetByPath(FilterConfiguration::COLUMNS_PATH, []);
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getDefaultFiltersState(DatagridConfiguration $datagridConfiguration): array
    {
        return $datagridConfiguration->offsetGetByPath(FilterConfiguration::DEFAULT_FILTERS_PATH, []);
    }
}
