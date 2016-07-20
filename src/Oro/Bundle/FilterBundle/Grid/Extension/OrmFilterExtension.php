<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Component\PhpUtils\ArrayUtil;

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

    /** @var ConfigurationProvider */
    protected $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigurationProvider $configurationProvider, TranslatorInterface $translator)
    {
        $this->configurationProvider = $configurationProvider;
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

        return $config->getDatasourceType() == OrmDatasource::TYPE;
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
                    $data = $form->getData();
                    if (isset($value['value']['start'])) {
                        $data['value']['start_original'] = $value['value']['start'];
                    }
                    if (isset($value['value']['end'])) {
                        $data['value']['end_original'] = $value['value']['end'];
                    }
                    $filter->apply($datasourceAdapter, $data);
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
        $initialFiltersState = $data->offsetGetByPath('[initialState][filters]', []);
        $filtersMetaData     = [];

        $filters       = $this->getFiltersToApply($config);
        $values        = $this->getValuesToApply($config);
        $initialValues = $this->getValuesToApply($config, false);
        $lazy          = $data->offsetGetOr(MetadataObject::LAZY_KEY, true);
        $filtersParams = $this->getParameters()->get(self::FILTER_ROOT_PARAM, []);
        $rawConfig     = $this->configurationProvider->isApplicable($config->getName())
            ? $this->configurationProvider->getRawConfiguration($config->getName())
            : [];

        foreach ($filters as $filter) {
            if (!$lazy) {
                $filter->resolveOptions();
            }
            $name             = $filter->getName();
            $value            = $this->getFilterValue($values, $name);
            $initialValue     = $this->getFilterValue($initialValues, $name);
            $filtersState        = $this->updateFilterStateEnabled($name, $filtersParams, $filtersState);
            $filtersState        = $this->updateFiltersState($filter, $value, $filtersState);
            $initialFiltersState = $this->updateFiltersState($filter, $initialValue, $initialFiltersState);

            $filter->setFilterState($value);
            $metadata          = $filter->getMetadata();
            $filtersMetaData[] = array_merge(
                $metadata,
                [
                    'label' => $metadata[FilterUtility::TRANSLATABLE_KEY]
                        ? $this->translator->trans($metadata['label'])
                        : $metadata['label'],
                    'cacheId' => $this->getFilterCacheId($rawConfig, $metadata),
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
     * @param array $rawGridConfig
     * @param array $filterMetadata
     *
     * @return string|null
     */
    protected function getFilterCacheId(array $rawGridConfig, array $filterMetadata)
    {
        if (empty($filterMetadata['lazy'])) {
            return null;
        }

        $rawOptions = ArrayUtil::getIn(
            $rawGridConfig,
            ['filters', 'columns', $filterMetadata['name'], 'options']
        );

        return $rawOptions ? md5(serialize($rawOptions)) : null;
    }

    /**
     * @param FilterInterface $filter
     * @param mixed           $value
     * @param array           $state
     *
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
            $filters            = [];

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

        foreach ($filtersConfig as $name => $definition) {
            if (isset($definition[PropertyInterface::DISABLED_KEY])
                && $definition[PropertyInterface::DISABLED_KEY]
            ) {
                // skip disabled filter
                continue;
            }

            // if label not set, try to suggest it from column with the same name
            if (!isset($definition['label'])) {
                $definition['label'] = $config->offsetGetByPath(
                    sprintf('[%s][%s][label]', FormatterConfiguration::COLUMNS_KEY, $name)
                );
            }
            $filters[] = $this->getFilterObject($name, $definition);
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
        } else {
            $currentFilters = $this->getParameters()->get(self::FILTER_ROOT_PARAM, []);
            return array_replace($defaultFilters, $currentFilters);
        }
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

    /**
     * @param array       $values
     * @param string      $key
     * @param mixed|false $default
     *
     * @return mixed
     */
    protected function getFilterValue(array $values, $key, $default = false)
    {
        return isset($values[$key]) ? $values[$key] : $default;
    }

    /**
     * Set state of filters(enable or disable) from parameters by special key - "__{$filterName}"
     *
     * @param string $name
     * @param array  $filtersParams
     * @param array  $state
     *
     * @return array
     */
    protected function updateFilterStateEnabled($name, array $filtersParams, array $state)
    {
        $filterEnabledKey = sprintf('__%s', $name);
        if (isset($filtersParams[$filterEnabledKey])) {
            $state[$filterEnabledKey] = $filtersParams[$filterEnabledKey];
        }

        return $state;
    }
}
