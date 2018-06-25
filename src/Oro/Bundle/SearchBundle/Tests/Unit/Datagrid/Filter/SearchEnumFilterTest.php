<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEnumFilter;

class SearchEnumFilterTest extends AbstractSearchEnumFilterTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->filter = new SearchEnumFilter($this->formFactory, $this->filterUtility, $this->dictionaryManager);
    }

    public function testApply()
    {
        $fieldName = 'field';
        $value = [
            'value1',
            'value2'
        ];

        /** @var SearchFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(new Comparison($fieldName, Comparison::IN, $value), FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => null,
                    'value' => $value,
                ]
            )
        );
    }
}
