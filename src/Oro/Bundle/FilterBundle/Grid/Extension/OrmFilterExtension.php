<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;

class OrmFilterExtension extends AbstractExtension
{
    /**
     * Query param
     */
    const FILTER_ROOT_PARAM     = '_filter';
    const MINIFIED_FILTER_PARAM = 'f';

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        if ($filters === null) {
            return false;
        }

        return $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(Configuration::FILTERS_PATH);
        // validate extension configuration and pass default values back to config
        $filtersNormalized = $this->validateConfiguration(
            new Configuration(array_keys($this->filters)),
            ['filters' => $filters]
        );
        // replace config values by normalized, extra keys passed directly
        $config->offsetSetByPath(
            Configuration::FILTERS_PATH,
            array_replace_recursive($filters, $filtersNormalized)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $filters = $this->getFiltersToApply($config);
        $values  = $this->getValuesToApply($config);
        /** @var OrmDatasource $datasource */
        $datasourceAdapter = new OrmFilterDatasourceAdapter($datasource->getQueryBuilder());

        foreach ($filters as $filter) {
            $value = isset($values[$filter->getName()]) ? $values[$filter->getName()] : false;

            if ($value !== false) {
                $form = $filter->getForm();
                if (!$form->isSubmitted()) {
                    $form->submit($value);
                }

                if ($form->isValid()) {
                    $filter->apply($datasourceAdapter, $form->getData());
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $filtersState        = $data->offsetGetByPath('[state][filters]', []);
        $initialFiltersState = $filtersState;
        $filtersMetaData     = [];

        $filters       = $this->getFiltersToApply($config);
        $values        = $this->getValuesToApply($config);
        $initialValues = $this->getValuesToApply($config, false);

        foreach ($filters as $filter) {
            $value        = isset($values[$filter->getName()]) ? $values[$filter->getName()] : false;
            $initialValue = isset($initialValues[$filter->getName()]) ? $initialValues[$filter->getName()] : false;

            $filtersState        = $this->updateFiltersState($filter, $value, $filtersState);
            $initialFiltersState = $this->updateFiltersState($filter, $initialValue, $initialFiltersState);

            $metadata          = $filter->getMetadata();
            $filtersMetaData[] = array_merge(
                $metadata,
                [
                    'label' => $metadata[FilterUtility::TRANSLATABLE_KEY]
                        ? $this->translator->trans($metadata['label'])
                        : $metadata['label']
                ]
            );

        }

        $data
            ->offsetAddToArray('initialState', ['filters' => $initialFiltersState])
            ->offsetAddToArray('state', ['filters' => $filtersState])
            ->offsetAddToArray('filters', $filtersMetaData)
            ->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orofilter/js/datafilter-builder']);
    }

    /**
     * @param FilterInterface $filter
     * @param mixed $value
     * @param array $state
     * @return array
     */
    protected function updateFiltersState(FilterInterface $filter, $value, array $state)
    {
        if ($value !== false) {
            $form = $filter->getForm();
            if (!$form->isSubmitted()) {
                $form->submit($value);
            }

            if ($form->isValid()) {
                $state[$filter->getName()] = $value;
            }
        }

        return $state;
    }

    /**
     * Add filter to array of available filters
     *
     * @param string          $name
     * @param FilterInterface $filter
     *
     * @return $this
     */
    public function addFilter($name, FilterInterface $filter)
    {
        $this->filters[$name] = $filter;

        return $this;
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $filters = [];

            if (array_key_exists(self::MINIFIED_FILTER_PARAM, $minifiedParameters)) {
                $filters = $minifiedParameters[self::MINIFIED_FILTER_PARAM];
            }

            $parameters->set(self::FILTER_ROOT_PARAM, $filters);
        }

        parent::setParameters($parameters);
    }

    /**
     * Prepare filters array
     *
     * @param DatagridConfiguration $config
     *
     * @return FilterInterface[]
     */
    protected function getFiltersToApply(DatagridConfiguration $config)
    {
        $filters       = [];
        $filtersConfig = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        foreach ($filtersConfig as $column => $filter) {
            // if label not set, try to suggest it from column with the same name
            if (!isset($filter['label'])) {
                $filter['label'] = $config->offsetGetByPath(
                    sprintf('[%s][%s][label]', FormatterConfiguration::COLUMNS_KEY, $column)
                );
            }
            $filters[] = $this->getFilterObject($column, $filter);
        }

        return $filters;
    }

    /**
     * Takes param from request and merge with default filters
     *
     * @param DatagridConfiguration $config
     * @param bool                  $readParameters
     *
     * @return array
     */
    protected function getValuesToApply(DatagridConfiguration $config, $readParameters = true)
    {
        $defaultFilters = $config->offsetGetByPath(Configuration::DEFAULT_FILTERS_PATH, []);

        if (!$readParameters) {
            return $defaultFilters;
        }

        $intersectKeys = array_intersect(
            $this->getParameters()->keys(),
            [
                OrmSorterExtension::SORTERS_ROOT_PARAM,
                PagerInterface::PAGER_ROOT_PARAM,
                ParameterBag::ADDITIONAL_PARAMETERS,
                ParameterBag::MINIFIED_PARAMETERS,
                self::FILTER_ROOT_PARAM,
                self::MINIFIED_FILTER_PARAM
            ]
        );

        $gridHasInitialState = empty($intersectKeys);

        if ($gridHasInitialState) {
            return $defaultFilters;
        }

        return $this->getParameters()->get(self::FILTER_ROOT_PARAM, []);
    }

    /**
     * Returns prepared filter object
     *
     * @param string $name
     * @param array  $config
     *
     * @return FilterInterface
     */
    protected function getFilterObject($name, array $config)
    {
        $type = $config[FilterUtility::TYPE_KEY];

        $filter = $this->filters[$type];
        $filter->init($name, $config);

        return clone $filter;
    }
}
