<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\NormalizeFilterKeys;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class NormalizeFilterKeysTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var NormalizeFilterKeys */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->processor = new NormalizeFilterKeys($this->doctrineHelper, $this->translator);
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
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($inputText) {
                    return '_' . $inputText;
                }
            );
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
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                [
                    'id'   => ['expectedKey' => 'filter[id]', 'expectedDescription' => '_oro.entity.identifier_field'],
                    'name' => ['expectedKey' => 'filter[name]', 'expectedDescription' => null]
                ]
            ],
            [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                [
                    'name'  => ['expectedKey' => 'filter[id]', 'expectedDescription' => '_oro.entity.identifier_field'],
                    'label' => ['expectedKey' => 'filter[label]', 'expectedDescription' => null],
                ]
            ],
        ];
    }
}
