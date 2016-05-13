<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class OrmSorterExtension extends AbstractExtension
{
    /**
     * Query param
     */
    const SORTERS_ROOT_PARAM = '_sort_by';
    const MINIFIED_SORTERS_PARAM = 's';

    /**
     * Ascending sorting direction
     */
    const DIRECTION_ASC = 'ASC';

    /**
     * Descending sorting direction
     */
    const DIRECTION_DESC = 'DESC';

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        $isApplicable = $config->getDatasourceType() === OrmDatasource::TYPE
            && is_array($columns);

        return $isApplicable;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->validateConfiguration(
            new Configuration(),
            ['sorters' => $config->offsetGetByPath(Configuration::SORTERS_PATH)]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $sorters = $this->getSortersToApply($config);
        foreach ($sorters as $definition) {
            list($direction, $sorter) = $definition;

            $sortKey = $sorter['data_name'];

            // if need customized behavior, just pass closure under "apply_callback" node
            if (isset($sorter['apply_callback']) && is_callable($sorter['apply_callback'])) {
                $sorter['apply_callback']($datasource, $sortKey, $direction);
            } else {
                $datasource->getQueryBuilder()->addOrderBy($sortKey, $direction);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $toolbarSort = $config->offsetGetByPath(Configuration::TOOLBAR_SORTING_PATH, false);
        $multiSort = $config->offsetGetByPath(Configuration::MULTISORT_PATH, false);
        if ($toolbarSort && $multiSort) {
            throw new LogicException('Columns multiple_sorting cannot be enabled for toolbar_sorting');
        }

        $this->processColumns($config, $data);

        $data->offsetAddToArray(MetadataObject::OPTIONS_KEY, ['multipleSorting' => $multiSort]);
        $toolbarOptions = $data->offsetGetByPath(ToolbarExtension::TOOLBAR_OPTION_PATH, []);
        $toolbarOptions['addSorting'] = $toolbarSort;
        $data->offsetSetByPath(ToolbarExtension::TOOLBAR_OPTION_PATH, $toolbarOptions);

        $this->setMetadataStates($config, $data);
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject $data
     */
    protected function processColumns(DatagridConfiguration $config, MetadataObject $data)
    {
        $toolbarSort = $config->offsetGetByPath(Configuration::TOOLBAR_SORTING_PATH, false);

        $sorters = $this->getSorters($config);

        $proceed = [];

        foreach ($data->offsetGetOr('columns', []) as $key => $column) {
            if (!array_key_exists('name', $column) || !array_key_exists($column['name'], $sorters)) {
                continue;
            }
            if ($toolbarSort && array_key_exists(PropertyInterface::TYPE_KEY, $sorters[$column['name']])) {
                $data->offsetSetByPath(
                    sprintf('[columns][%s][sortingType]', $key),
                    $sorters[$column['name']][PropertyInterface::TYPE_KEY]
                );
            }
            $data->offsetSetByPath(sprintf('[columns][%s][sortable]', $key), true);
            $proceed[] = $column['name'];
        }

        $extraSorters = array_diff(array_keys($sorters), $proceed);
        if (count($extraSorters)) {
            throw new LogicException(
                sprintf('Could not found column(s) "%s" for sorting', implode(', ', $extraSorters))
            );
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject $data
     */
    protected function setMetadataStates(DatagridConfiguration $config, MetadataObject $data)
    {
        $sortersState = $this->getSortersState($config, $data);
        $initialSortersState = $this->getSortersState($config, $data, false);

        $data->offsetAddToArray('initialState', ['sorters' => $initialSortersState]);
        $data->offsetAddToArray('state', ['sorters' => $sortersState]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // should visit after all extensions
        return -260;
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $sorters = [];

            if (array_key_exists(self::MINIFIED_SORTERS_PARAM, $minifiedParameters)) {
                $sorters = $minifiedParameters[self::MINIFIED_SORTERS_PARAM];
                if (is_array($sorters)) {
                    foreach ($sorters as $field => $direction) {
                        $sorters[$field] = $direction > 0
                            ? self::DIRECTION_DESC
                            : self::DIRECTION_ASC;
                    }
                }
            }

            $parameters->set(self::SORTERS_ROOT_PARAM, $sorters);
        }

        parent::setParameters($parameters);
    }

    /**
     * Retrieve and prepare list of sorters
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getSorters(DatagridConfiguration $config)
    {
        $sorters = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        foreach ($sorters as $name => $definition) {
            if (isset($definition[PropertyInterface::DISABLED_KEY]) && $definition[PropertyInterface::DISABLED_KEY]) {
                // remove disabled sorter
                unset($sorters[$name]);
            } else {
                $definition = is_array($definition) ? $definition : [];
                $sorters[$name] = $definition;
            }
        }

        return $sorters;
    }

    /**
     * Prepare sorters array
     *
     * @param DatagridConfiguration $config
     * @param bool $readParameters
     *
     * @return array
     */
    protected function getSortersToApply(DatagridConfiguration $config, $readParameters = true)
    {
        $result = [];

        $sorters = $this->getSorters($config);

        $defaultSorters = $config->offsetGetByPath(Configuration::DEFAULT_SORTERS_PATH, []);
        if ($readParameters) {
            $sortBy = $this->getParameters()->get(self::SORTERS_ROOT_PARAM) ?: $defaultSorters;
        } else {
            $sortBy = $defaultSorters;
        }

        // if default sorter was not specified, just take first sortable column
        if (!$sortBy && $sorters) {
            $names = array_keys($sorters);
            $firstSorterName = reset($names);
            $sortBy = [$firstSorterName => self::DIRECTION_ASC];
        }

        foreach ($sortBy as $column => $direction) {
            $sorter = isset($sorters[$column]) ? $sorters[$column] : false;

            if ($sorter !== false) {
                $direction = $this->normalizeDirection($direction);
                $result[$column] = [$direction, $sorter];
            }
        }

        return $result;
    }

    /**
     * Normalize user input
     *
     * @param string $direction
     *
     * @return string
     */
    protected function normalizeDirection($direction)
    {
        switch (true) {
            case in_array($direction, [self::DIRECTION_ASC, self::DIRECTION_DESC], true):
                break;
            case ($direction === false):
                $direction = self::DIRECTION_DESC;
                break;
            default:
                $direction = self::DIRECTION_ASC;
        }

        return $direction;
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject $data
     * @param bool $readParameters
     * @return array
     */
    protected function getSortersState(DatagridConfiguration $config, MetadataObject $data, $readParameters = true)
    {
        $sortersState = $data->offsetGetByPath('[state][sorters]', []);
        $sorters = $this->getSortersToApply($config, $readParameters);

        foreach ($sorters as $column => $definition) {
            list($direction) = $definition;
            $sortersState[$column] = $this->normalizeDirection($direction);
        }

        return $sortersState;
    }
}
