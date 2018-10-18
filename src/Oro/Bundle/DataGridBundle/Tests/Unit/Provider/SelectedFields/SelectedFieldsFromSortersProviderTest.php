<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromSortersProvider;

class SelectedFieldsFromSortersProviderTest extends AbstractSelectedFieldsProviderTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new SelectedFieldsFromSortersProvider($this->datagridStateProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGetConfiguration(array $configuration): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->with(SorterConfiguration::COLUMNS_PATH)
            ->willReturn($configuration);
    }
}
