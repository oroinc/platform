<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Updates datagrid metadata object with:
 * - initial filters state - as per datagrid sorters configuration;
 * - filters state - as per current state based on columns configuration, grid view settings and datagrid parameters;
 * - updates metadata with filters config.
 */
abstract class AbstractFilterExtension extends AbstractExtension
{
    public const FILTER_ROOT_PARAM = '_filter';
    public const MINIFIED_FILTER_PARAM = 'f';

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigurationProvider */
    protected $configurationProvider;

    /** @var DatagridStateProviderInterface */
    protected $filtersStateProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     * @param DatagridStateProviderInterface $filtersStateProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigurationProvider $configurationProvider,
        DatagridStateProviderInterface $filtersStateProvider,
        TranslatorInterface $translator
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->filtersStateProvider = $filtersStateProvider;
        $this->translator = $translator;
    }

    /**
     * Add filter to array of available filters
     *
     * @param string $name
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
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(Configuration::FILTERS_PATH);

        // Validates extension configuration and passes default values back to config.
        $filtersNormalized = $this->validateConfiguration(
            new Configuration(array_keys($this->filters)),
            ['filters' => $filters]
        );

        // Replaces config values by normalized, extra keys passed directly.
        $config->offsetSetByPath(Configuration::FILTERS_PATH, array_replace_recursive($filters, $filtersNormalized));
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $metadata)
    {
        $filters = $this->getFiltersToApply($config);

        $this->updateState($filters, $config, $metadata);
        $this->updateMetadata($filters, $config, $metadata);

        $metadata->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orofilter/js/datafilter-builder']);
    }

    /**
     * Prepare filters array
     *
     * @param DatagridConfiguration $config
     *
     * @return FilterInterface[]
     */
    protected function getFiltersToApply(DatagridConfiguration $config): array
    {
        $filters = [];
        $filtersConfig = $config->offsetGetByPath(Configuration::COLUMNS_PATH, []);

        foreach ($filtersConfig as $filterName => $filterConfig) {
            if (!empty($filterConfig[PropertyInterface::DISABLED_KEY])) {
                // Skips disabled filter.
                continue;
            }

            // If label is not set, tries to use corresponding column label.
            if (!isset($filterConfig['label'])) {
                $filterConfig['label'] = $config->offsetGetByPath(
                    sprintf('[%s][%s][label]', FormatterConfiguration::COLUMNS_KEY, $filterName)
                );
            }
            $filters[$filterName] = $this->getFilterObject($filterName, $filterConfig);
        }

        return $filters;
    }

    /**
     * Returns prepared filter object
     *
     * @param string $filterName
     * @param array $filterConfig
     *
     * @return FilterInterface
     */
    protected function getFilterObject($filterName, array $filterConfig): FilterInterface
    {
        $filterType = $filterConfig[FilterUtility::TYPE_KEY];

        $filter = $this->filters[$filterType];
        $filter->init($filterName, $filterConfig);

        // Ensures filter is "somewhat-stateless" across datagrids.
        // "Somewhat stateless" means that some filters cannot be fully stateless, because there are filters that
        // are used directly as a service, e.g. oro_filter.date_grouping_filter. That is why we cannot clone filter
        // before calling "init".
        return clone $filter;
    }

    /**
     * @param FilterInterface[] $filters
     * @param DatagridConfiguration $config
     * @param MetadataObject $metadata
     */
    protected function updateState(array $filters, DatagridConfiguration $config, MetadataObject $metadata): void
    {
        $filtersState = $this->filtersStateProvider->getState($config, $this->getParameters());
        $initialFiltersState = $this->filtersStateProvider->getDefaultState($config);

        foreach ($filters as $filterName => $filter) {
            $value = $filtersState[$filterName] ?? null;
            $initialValue = $initialFiltersState[$filterName] ?? null;
            if ($value === null && $initialValue === null) {
                continue;
            }

            if (!$this->isLazy($metadata)) {
                // Resolves options to make it possible to submit & validate the filter form.
                $filter->resolveOptions();
            }

            // Submits filter initial state value to check if it is valid.
            if ($initialValue !== null && !$this->submitFilter($filter, $initialValue)->isValid()) {
                // Excludes invalid filter value from initial state.
                unset($initialFiltersState[$filterName]);
            }

            // Submits filter state value and checks if it is valid.
            if ($value !== null && !$this->submitFilter($filter, $value)->isValid()) {
                // Excludes invalid filter value from state.
                unset($filtersState[$filterName]);
            }

            $filter->setFilterState($value);
        }

        $metadata
            ->offsetAddToArray('initialState', ['filters' => $initialFiltersState])
            ->offsetAddToArray('state', ['filters' => $filtersState]);
    }

    /**
     * @param MetadataObject $metadata
     *
     * @return bool
     */
    protected function isLazy(MetadataObject $metadata): bool
    {
        return (bool)$metadata->offsetGetOr(MetadataObject::LAZY_KEY, true);
    }

    /**
     * Submits filter form with filter state (i.e. value).
     * Works with cloned form to ensure filter is stateless.
     *
     * @param FilterInterface $filter
     * @param array $filterState
     *
     * @return FormInterface
     */
    protected function submitFilter(FilterInterface $filter, array $filterState): FormInterface
    {
        $filterForm = clone $filter->getForm();
        $filterForm->submit($filterState);

        return $filterForm;
    }

    /**
     * @param FilterInterface[] $filters
     * @param DatagridConfiguration $config
     * @param MetadataObject $metadata
     */
    protected function updateMetadata(
        array $filters,
        DatagridConfiguration $config,
        MetadataObject $metadata
    ): void {
        $rawConfig = $this->configurationProvider->isApplicable($config->getName())
            ? $this->configurationProvider->getRawConfiguration($config->getName())
            : [];

        $filtersMetadata = [];
        foreach ($filters as $filter) {
            $filterMetadata = $filter->getMetadata();
            $label = $filterMetadata['label'] ?? '';

            $filtersMetadata[] = array_merge(
                $filterMetadata,
                [
                    'label' => !empty($filterMetadata[FilterUtility::TRANSLATABLE_KEY])
                        ? $this->translator->trans($label)
                        : $label,
                    'cacheId' => $this->getFilterCacheId($rawConfig, $filterMetadata),
                ]
            );
        }

        $metadata->offsetAddToArray('filters', $filtersMetadata);
    }

    /**
     * @param array $rawGridConfig
     * @param array $filterMetadata
     *
     * @return string|null
     */
    protected function getFilterCacheId(array $rawGridConfig, array $filterMetadata): ?string
    {
        if (empty($filterMetadata['lazy'])) {
            return null;
        }

        $rawOptions = ArrayUtil::getIn($rawGridConfig, ['filters', 'columns', $filterMetadata['name'], 'options']);

        return $rawOptions ? md5(serialize($rawOptions)) : null;
    }
}
