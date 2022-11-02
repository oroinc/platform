<?php

namespace Oro\Bundle\FilterBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Composite provider that polls inner datagrid filters providers.
 */
class DatagridFiltersProviderComposite implements DatagridFiltersProviderInterface
{
    /** @var iterable|DatagridFiltersProviderInterface[] */
    private iterable $datagridFiltersProviders;

    /**
     * @param iterable|DatagridFiltersProviderInterface[] $datagridFiltersProviders
     */
    public function __construct(iterable $datagridFiltersProviders)
    {
        $this->datagridFiltersProviders = $datagridFiltersProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatagridFilters(DatagridConfiguration $gridConfig): array
    {
        $filters = [[]];
        foreach ($this->datagridFiltersProviders as $filtersProvider) {
            $filters[] = $filtersProvider->getDatagridFilters($gridConfig);
        }

        return array_merge(...$filters);
    }
}
