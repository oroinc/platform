<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\CorrectSortValue;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class CorrectSortValueTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var CorrectSortValue */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new CorrectSortValue($this->doctrineHelper);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($className, $requestString, $expectedResult)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=' . $requestString);

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $filterValue = $filterValues->get('sort');
        $this->assertEquals($expectedResult, $filterValue->getValue());
    }

    public function processProvider()
    {
        return [
            'entity with "id" index field and "id" sorter request string'          => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'id',
                'id'
            ],
            'entity with "id" index field and "-id" sorter request string'         => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                '-id',
                '-id'
            ],
            'entity with "id" index field and "id,label" sorter request string'    => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'id,label',
                'id,label'
            ],
            'entity with "name" index field and "id" sorter request string'        => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                'id',
                'name'
            ],
            'entity with "name" index field and "-id" sorter request string'       => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                '-id',
                '-name'
            ],
            'entity with "name" index field and "-id,label" sorter request string' => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                '-id,label',
                '-name,label'
            ]
        ];
    }

    /**
     * @dataProvider processDefaultValueProvider
     */
    public function testProcessDefaultValue($className, $defaultValueString, $expectedResult)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');

        $this->context->getFilters()->add(
            'sort',
            new SortFilter(
                DataType::ORDER_BY,
                '',
                null,
                function () use ($defaultValueString) {
                    return $defaultValueString;
                }
            )
        );
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $filterValue = $filterValues->get('sort');
        $this->assertEquals($expectedResult, $filterValue->getValue());
    }

    public function processDefaultValueProvider()
    {
        return [
            'entity with "id" index field and "id" default value'   => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'id',
                'id'
            ],
            'entity with "name" index field and "id" default value' => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                'id',
                'name'
            ],
        ];
    }

    public function testProcessNoDefaultValue()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');

        $this->context->getFilters()->add(
            'sort',
            new SortFilter(DataType::ORDER_BY)
        );
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $this->assertNull($filterValues->get('sort'));
    }

    public function testProcessNoFilter()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $this->assertNull($filterValues->get('sort'));
    }
}
