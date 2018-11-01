<?php

namespace Oro\Bundle\DataGridBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

/**
 * Describes a provider which has to return an array representing state of some datagrid component.
 * A state is represented by an array which contains request- and user-specific data about current datagrid component
 * settings (state), e.g. for columns it can contain information for each column about whether it is renderable
 * (visible) and its order (weight). Initially, due to specifics of datagrid frontend implementation, a datagrid state
 * has been introduced for usage in frontend - to adjust datagrid view according to user preferences, e.g. show only
 * specific columns in specific order. Later a datagrid state has been started used on backend, e.g. for sorters and
 * adjustment of datasource queries.
 */
interface DatagridStateProviderInterface
{
    /**
     * Returns state based on parameters, grid view and configuration.
     *
     * @param DatagridConfiguration $datagridConfiguration
     * @param ParameterBag $datagridParameters
     *
     * @return array State of datagrid component, e.g. columns` state, sorters state
     */
    public function getState(DatagridConfiguration $datagridConfiguration, ParameterBag $datagridParameters);

    /**
     * Returns state based on parameters and configuration.
     *
     * @param DatagridConfiguration $datagridConfiguration
     * @param ParameterBag $datagridParameters
     *
     * @return array State of datagrid component, e.g. columns` state, sorters state
     */
    public function getStateFromParameters(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    );

    /**
     * Returns default state based on configuration only.
     *
     * @param DatagridConfiguration $datagridConfiguration
     *
     * @return array State of datagrid component, e.g. columns` state, sorters state
     */
    public function getDefaultState(DatagridConfiguration $datagridConfiguration);
}
