<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\DataFixtures\LoadItemsWithBooleanValue;

/**
 * Tests that grid with default filter value correctly handles filter reset.
 *
 * @dbIsolationPerTest
 */
class DefaultFilterValueTest extends AbstractDatagridTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadItemsWithBooleanValue::class]);
    }

    #[\Override]
    public function gridProvider(): array
    {
        return [
            'Grid without filter params should apply default filter (booleanValue = YES)' => [
                [
                    'gridParameters' => [
                        'gridName' => 'items-grid-with-default-filter',
                    ],
                    'gridFilters' => [],
                    'assert' => [],
                    // Only item with booleanValue = true should be returned (default filter)
                    'expectedResultCount' => 1,
                ],
            ],
            'Grid with explicit empty filter should show all records' => [
                [
                    'gridParameters' => [
                        'gridName' => 'items-grid-with-default-filter',
                    ],
                    'gridFilters' => [
                        // Empty value means "show all" - should override default filter
                        'items-grid-with-default-filter[_filter][booleanValue][value]' => '',
                    ],
                    'assert' => [],
                    // All 3 items should be returned when filter is explicitly reset
                    'expectedResultCount' => 3,
                ],
            ],
            'Grid with explicit YES filter should show only true records' => [
                [
                    'gridParameters' => [
                        'gridName' => 'items-grid-with-default-filter',
                    ],
                    'gridFilters' => [
                        'items-grid-with-default-filter[_filter][booleanValue][value]' => BooleanFilterType::TYPE_YES,
                    ],
                    'assert' => [],
                    'expectedResultCount' => 1,
                ],
            ],
            'Grid with explicit NO filter should show only false records' => [
                [
                    'gridParameters' => [
                        'gridName' => 'items-grid-with-default-filter',
                    ],
                    'gridFilters' => [
                        'items-grid-with-default-filter[_filter][booleanValue][value]' => BooleanFilterType::TYPE_NO,
                    ],
                    'assert' => [],
                    'expectedResultCount' => 1,
                ],
            ],
        ];
    }
}
