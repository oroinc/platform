<?php

namespace Oro\Bundle\DataGridBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

/**
 * Returns selected fields from all child providers.
 */
class SelectedFieldsProvider implements SelectedFieldsProviderInterface
{
    /** @var iterable|SelectedFieldsProviderInterface[] */
    private $selectedFieldsProviders;

    /**
     * @param iterable|SelectedFieldsProviderInterface[] $selectedFieldsProviders
     */
    public function __construct(iterable $selectedFieldsProviders)
    {
        $this->selectedFieldsProviders = $selectedFieldsProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectedFields(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array {
        $result = [];
        foreach ($this->selectedFieldsProviders as $selectedFieldsProvider) {
            $result[] = $selectedFieldsProvider->getSelectedFields($datagridConfiguration, $datagridParameters);
        }
        if ($result) {
            $result = array_unique(array_merge(...$result));
        }

        return $result;
    }
}
