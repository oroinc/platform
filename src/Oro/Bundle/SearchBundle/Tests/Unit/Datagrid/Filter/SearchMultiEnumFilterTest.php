<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchMultiEnumFilter;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class SearchMultiEnumFilterTest extends AbstractSearchEnumFilterTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->filter = new SearchMultiEnumFilter($this->formFactory, $this->filterUtility, $this->dictionaryManager);
    }

    public function testApply()
    {
        $fieldName = 'field_' . EnumIdPlaceholder::NAME;
        $value = [
            'value1',
            'value2'
        ];

        /** @var SearchFilterDatasourceAdapter|\PHPUnit_Framework_MockObject_MockObject $ds */
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('field_value1', Comparison::EQ, 1),
                        new Comparison('field_value2', Comparison::EQ, 1),
                    ]
                )
            );

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
