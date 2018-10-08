<?php

namespace Oro\Bundle\DataGridBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides request- and user-specific datagrid state for sorters component.
 * Tries to fetch state from datagrid parameters, then fallbacks to state from current datagrid view, then from default
 * datagrid view, then to datagrid columns configuration.
 * State is respresented by an array with column names as key and order direction as value.
 */
class SortersStateProvider extends AbstractStateProvider
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
        // Fetch state from datagrid parameters.
        $state = $this->getFromParameters($datagridParameters);

        // Try to fetch state from grid view.
        if (!$state) {
            $gridView = $this->getActualGridView($datagridConfiguration, $datagridParameters);
            if ($gridView) {
                $state = $gridView->getSortersData();
            }
        }

        // Fallback to default sorters.
        if (!$state) {
            $state = $this->getDefaultSorters($datagridConfiguration);
        }

        return $this->sanitizeState($state, $this->getSortersConfig($datagridConfiguration));
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

        // Fallback to default sorters.
        if (!$state) {
            $state = $this->getDefaultSorters($datagridConfiguration);
        }

        return $this->sanitizeState($state, $this->getSortersConfig($datagridConfiguration));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultState(DatagridConfiguration $datagridConfiguration): array
    {
        $state = $this->getDefaultSorters($datagridConfiguration);

        return $this->sanitizeState($state, $this->getSortersConfig($datagridConfiguration));
    }

    /**
     * @param array $state
     * @param array $sortersConfig
     *
     * @return array
     */
    private function sanitizeState(array $state, array $sortersConfig): array
    {
        // Remove sorters which are not in datagrid configuration.
        $state = array_intersect_key($state, $sortersConfig);

        array_walk($state, [$this, 'normalizeDirection']);

        return $state;
    }

    /**
     * {@inheritdoc}
     */
    private function getFromParameters(ParameterBag $datagridParameters): array
    {
        $sortersState = $this->datagridParametersHelper
            ->getFromParameters($datagridParameters, AbstractSorterExtension::SORTERS_ROOT_PARAM);

        // Try to fetch from minified parameters if any.
        if (!$sortersState) {
            $sortersState = $this->datagridParametersHelper
                ->getFromMinifiedParameters($datagridParameters, AbstractSorterExtension::MINIFIED_SORTERS_PARAM);
        }

        return (array)$sortersState;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getSortersConfig(DatagridConfiguration $datagridConfiguration): array
    {
        return array_filter(
            $datagridConfiguration->offsetGetByPath(SorterConfiguration::COLUMNS_PATH, []),
            function (array $sorterDefinition) {
                return empty($sorterDefinition[PropertyInterface::DISABLED_KEY]);
            }
        );
    }

    /**
     * @param string|int|bool $direction
     */
    private function normalizeDirection(&$direction): void
    {
        switch ($direction) {
            case AbstractSorterExtension::DIRECTION_ASC:
            case AbstractSorterExtension::DIRECTION_DESC:
                break;
            case 1:
            case false:
                $direction = AbstractSorterExtension::DIRECTION_DESC;
                break;
            case -1:
            default:
                $direction = AbstractSorterExtension::DIRECTION_ASC;
        }
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array
     */
    private function getDefaultSorters(DatagridConfiguration $datagridConfiguration): array
    {
        return $datagridConfiguration->offsetGetByPath(SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false)
            ? []
            : $datagridConfiguration->offsetGetByPath(SorterConfiguration::DEFAULT_SORTERS_PATH, []);
    }
}
