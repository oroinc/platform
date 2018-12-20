<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;

/**
 * Applies sorters to datasource.
 * Updates datagrid metadata object with:
 * - initial sorters state - as per datagrid sorters configuration;
 * - sorters state - as per current state based on columns configuration, grid view settings and datagrid parameters;
 * - updates metadata with sorters config.
 */
abstract class AbstractSorterExtension extends AbstractExtension
{
    public const SORTERS_ROOT_PARAM = '_sort_by';
    public const MINIFIED_SORTERS_PARAM = 's';

    public const DIRECTION_ASC = 'ASC';
    public const DIRECTION_DESC = 'DESC';

    /** @var DatagridStateProviderInterface */
    private $sortersStateProvider;

    /**
     * @param DatagridStateProviderInterface $sortersStateProvider
     */
    public function __construct(DatagridStateProviderInterface $sortersStateProvider)
    {
        $this->sortersStateProvider = $sortersStateProvider;
    }

    /**
     * @param array               $sorter
     * @param string              $direction
     * @param DatasourceInterface $datasource
     */
    abstract protected function addSorterToDatasource(array $sorter, $direction, DatasourceInterface $datasource);

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        return
            parent::isApplicable($config)
            && is_array($columns);
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
        $sortersConfig = $this->getSorters($config);
        $sortersState = $this->sortersStateProvider->getStateFromParameters($config, $this->getParameters());
        foreach ($sortersState as $sorterName => $direction) {
            $sorter = $sortersConfig[$sorterName];

            // if need customized behavior, just pass closure under "apply_callback" node
            if (isset($sorter['apply_callback']) && \is_callable($sorter['apply_callback'])) {
                $sorter['apply_callback']($datasource, $sorter['data_name'], $direction);
            } else {
                $this->addSorterToDatasource($sorter, $direction, $datasource);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $toolbarSort = $config->offsetGetByPath(Configuration::TOOLBAR_SORTING_PATH, false);
        $multiSort   = $config->offsetGetByPath(Configuration::MULTISORT_PATH, false);
        $defaultSorting = $config->offsetGetByPath(Configuration::DEFAULT_SORTERS_PATH, false);
        $disableDefaultSorting = $config->offsetGetByPath(Configuration::DISABLE_DEFAULT_SORTING_PATH, false);
        $disableNotSelectedOption   = $config->offsetGetByPath(Configuration::DISABLE_NOT_SELECTED_OPTION_PATH, false);

        if ($toolbarSort && $multiSort) {
            throw new LogicException('Columns multiple_sorting cannot be enabled for toolbar_sorting');
        }

        $this->processColumns($config, $data);

        $data->offsetAddToArray(MetadataObject::OPTIONS_KEY, ['multipleSorting' => $multiSort]);
        $toolbarOptions               = $data->offsetGetByPath(ToolbarExtension::TOOLBAR_OPTION_PATH, []);
        $toolbarOptions['addSorting'] = $toolbarSort;
        $toolbarOptions['disableNotSelectedOption'] = $disableNotSelectedOption
            && !empty($defaultSorting)
            && !$disableDefaultSorting;
        $data->offsetSetByPath(ToolbarExtension::TOOLBAR_OPTION_PATH, $toolbarOptions);

        $this->setMetadataStates($config, $data);
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
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
     * @param MetadataObject        $data
     */
    protected function setMetadataStates(DatagridConfiguration $config, MetadataObject $data)
    {
        $sortersState = $this->sortersStateProvider->getState($config, $this->getParameters());
        $data->offsetAddToArray('state', ['sorters' => $sortersState]);

        $initialSortersState = $this->sortersStateProvider->getDefaultState($config);
        $data->offsetAddToArray('initialState', ['sorters' => $initialSortersState]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should visit after all extensions
        return -260;
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
                $definition     = is_array($definition) ? $definition : [];
                $sorters[$name] = $definition;
            }
        }

        return $sorters;
    }
}
