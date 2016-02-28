<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Processor\GetList\BuildQuery;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\NormalizeFilterKeys;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class NormalizeFilterKeysTest extends OrmRelatedTestCase
{
    /** @var NormalizeFilterKeys */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->processor = new NormalizeFilterKeys($this->doctrineHelper, $this->translator);

        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context          = new GetListContext($configProvider, $metadataProvider);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setQuery($qb);

        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className      = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User';
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $processor = new BuildQuery($doctrineHelper);
        $this->context->setClassName($className);
        $processor->process($this->context);

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
