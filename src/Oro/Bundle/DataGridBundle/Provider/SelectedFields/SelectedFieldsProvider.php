<?php

namespace Oro\Bundle\DataGridBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

/**
 * Composite provider which returns selected fields from all inner providers.
 */
class SelectedFieldsProvider implements SelectedFieldsProviderInterface
{
    /** @var SelectedFieldsProviderInterface[] */
    private $selectedFieldsProviders = [];

    /**
     * @param SelectedFieldsProviderInterface $selectedFieldsProvider
     */
    public function addSelectedFieldsProvider(SelectedFieldsProviderInterface $selectedFieldsProvider)
    {
        $this->selectedFieldsProviders[] = $selectedFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectedFields(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array {
        $selectedFields = [[]];
        foreach ($this->selectedFieldsProviders as $selectedFieldsProvider) {
            $selectedFields[] = $selectedFieldsProvider
                ->getSelectedFields($datagridConfiguration, $datagridParameters);
        }

        return array_unique(array_merge(...$selectedFields));
    }
}
