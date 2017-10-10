<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeFilterKeys;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class NormalizeFilterKeysTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var NormalizeFilterKeys */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new NormalizeFilterKeys($this->doctrineHelper);
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
    public function testProcess($className, $filters)
    {
        $filtersDefinition = new FilterCollection();
        foreach (array_keys($filters) as $fieldName) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            $filtersDefinition->add($fieldName, $filter);
        }

        $this->context->set('filters', $filtersDefinition);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $filtersDefinition = $this->context->getFilters();
        foreach ($filtersDefinition as $filterKey => $filterDefinition) {
            $fieldName = $filterDefinition->getField();
            $this->assertArrayHasKey($fieldName, $filters);
            $this->assertEquals($filters[$fieldName]['expectedKey'], $filterKey);
            $this->assertEquals($filters[$fieldName]['expectedDescription'], $filterDefinition->getDescription());
        }
    }

    public function processProvider()
    {
        return [
            [
                Entity\User::class,
                [
                    'id'   => [
                        'expectedKey' => 'filter[id]',
                        'expectedDescription' => NormalizeFilterKeys::ID_FILTER_DESCRIPTION
                    ],
                    'name' => [
                        'expectedKey' => 'filter[name]',
                        'expectedDescription' => null
                    ]
                ]
            ],
            [
                Entity\Category::class,
                [
                    'name'  => [
                        'expectedKey' => 'filter[id]',
                        'expectedDescription' => NormalizeFilterKeys::ID_FILTER_DESCRIPTION
                    ],
                    'label' => [
                        'expectedKey' => 'filter[label]',
                        'expectedDescription' => null
                    ],
                ]
            ],
        ];
    }
}
