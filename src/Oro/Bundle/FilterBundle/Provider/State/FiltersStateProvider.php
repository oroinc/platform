<?php

namespace Oro\Bundle\FilterBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\State\AbstractStateProvider;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\DependencyInjection\ServiceLink;

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
     * {@inheritdoc}
     */
    public function getState(DatagridConfiguration $datagridConfiguration, ParameterBag $datagridParameters): array
    {
        $state = [];
        $defaultState = $this->getDefaultFiltersState($datagridConfiguration);

        // Fetch state from datagrid parameters.
        $stateFromParameters = $this->getStateFromParameters($datagridParameters);
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
        // Remove filters which are not in datagrid configuration.
        $filters = $this->getFilters($datagridConfiguration);
        $state = array_filter(
            $state,
            function (string $filterName) use ($filters) {
                if (isset($filters[$filterName])) {
                    return true;
                }

                // Allows filters with special key - "__{$filterName}".
                // Initially was added to AbstractFilterExtension::updateFilterStateEnabled() in scope of CRM-4760.
                if (strpos($filterName, '__') === 0) {
                    $originalFilterName = substr($filterName, 2);
                    return isset($filters[$originalFilterName]);
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
    private function getStateFromParameters(ParameterBag $datagridParameters): array
    {
        $filtersState = $this->datagridParametersHelper
            ->getFromParameters($datagridParameters, AbstractFilterExtension::FILTER_ROOT_PARAM);

        // Try to fetch from minified parameters if any.
        if (!$filtersState) {
            $filtersState = $this->datagridParametersHelper
                ->getFromMinifiedParameters($datagridParameters, AbstractFilterExtension::MINIFIED_FILTER_PARAM);
        }

        return (array)$filtersState;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getFilters(DatagridConfiguration $datagridConfiguration)
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
