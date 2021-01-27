<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmManyRelationBuilder;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\MultiEnumFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEnumValue;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class MultiEnumFilterTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    private $em;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var MultiEnumFilter */
    private $filter;

    protected function setUp(): void
    {
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces([
            'Stub' => 'Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures'
        ]);

        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $manyRelationBuilder = new ManyRelationBuilder();
        $manyRelationBuilder->addBuilder(new OrmManyRelationBuilder($doctrine));

        $this->filter = new MultiEnumFilter(
            $this->formFactory,
            new FilterUtility(),
            $manyRelationBuilder
        );
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        $paramsProperty = new \ReflectionProperty($this->filter, 'params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($this->filter);

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => []],
            $params
        );
    }

    public function testInitWithNullValue()
    {
        $this->filter->init('test', ['null_value' => ':empty:']);

        $paramsProperty = new \ReflectionProperty($this->filter, 'params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($this->filter);

        self::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'null_value' => ':empty:',
                'options' => []
            ],
            $params
        );
    }

    public function testInitWithClass()
    {
        $this->filter->init('test', ['class' => 'Test\EnumValue']);

        $paramsProperty = new \ReflectionProperty($this->filter, 'params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($this->filter);

        self::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options' => [
                    'class' => 'Test\EnumValue'
                ]
            ],
            $params
        );
    }

    public function testInitWithEnumCode()
    {
        $this->filter->init('test', ['enum_code' => 'test_enum']);

        $paramsProperty = new \ReflectionProperty($this->filter, 'params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($this->filter);

        self::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options' => [
                    'enum_code' => 'test_enum'
                ]
            ],
            $params
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(EnumFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(array $values, $comparisonType, string $expectedDQL)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestEntity', 'o');

        $data = [
            'value' => $values,
            'type' => $comparisonType,
        ];

        $params = [
            'null_value' => ':empty:',
            FilterUtility::DATA_NAME_KEY => 'o.values'
        ];
        $this->filter->init('test', $params);

        /** @var OrmFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->expects(self::any())
            ->method('generateParameterName')
            ->willReturn('param1');

        $this->filter->apply($ds, $data);

        self::assertEquals($expectedDQL, $qb->getQuery()->getDQL());
        self::assertEquals($values, $qb->getParameter('param1')->getValue());
    }

    public function applyDataProvider(): array
    {
        return [
            [
                'values' => [
                    new TestEnumValue('val1', 'Value1'),
                    new TestEnumValue('val2', 'Value2')
                ],
                'comparisonType' => null,
                'expectedDQL' => 'SELECT o.id FROM Stub:TestEntity o'
                    . ' WHERE o IN('
                    . 'SELECT filter_param1'
                    . ' FROM Stub:TestEntity filter_param1'
                    . ' INNER JOIN filter_param1.values filter_param1_rel'
                    . ' WHERE filter_param1_rel IN(:param1))',
            ],
            [
                'values' => [
                    new TestEnumValue('val1', 'Value1'),
                    new TestEnumValue('val2', 'Value2')
                ],
                'comparisonType' => DictionaryFilterType::TYPE_NOT_IN,
                'expectedDQL' => 'SELECT o.id FROM Stub:TestEntity o'
                    . ' WHERE o NOT IN('
                    . 'SELECT filter_param1'
                    . ' FROM Stub:TestEntity filter_param1'
                    . ' INNER JOIN filter_param1.values filter_param1_rel'
                    . ' WHERE filter_param1_rel IN(:param1))',
            ],
            [
                'values' => [
                    new TestEnumValue('val1', 'Value1'),
                    new TestEnumValue('val2', 'Value2')
                ],
                'comparisonType' => (string)DictionaryFilterType::TYPE_NOT_IN,
                'expectedDQL' => 'SELECT o.id FROM Stub:TestEntity o'
                    . ' WHERE o NOT IN('
                    . 'SELECT filter_param1'
                    . ' FROM Stub:TestEntity filter_param1'
                    . ' INNER JOIN filter_param1.values filter_param1_rel'
                    . ' WHERE filter_param1_rel IN(:param1))',
            ],
            [
                'values' => [
                    new TestEnumValue('val1', 'Value1'),
                    new TestEnumValue('val2', 'Value2')
                ],
                'comparisonType' => DictionaryFilterType::NOT_EQUAL,
                'expectedDQL' => 'SELECT o.id FROM Stub:TestEntity o'
                    . ' WHERE o NOT IN('
                    . 'SELECT filter_param1'
                    . ' FROM Stub:TestEntity filter_param1'
                    . ' INNER JOIN filter_param1.values filter_param1_rel'
                    . ' WHERE filter_param1_rel IN(:param1))',
            ],
            [
                'values' => [
                    new TestEnumValue('val1', 'Value1'),
                    new TestEnumValue('val2', 'Value2')
                ],
                'comparisonType' => (string)DictionaryFilterType::NOT_EQUAL,
                'expectedDQL' => 'SELECT o.id FROM Stub:TestEntity o'
                    . ' WHERE o NOT IN('
                    . 'SELECT filter_param1'
                    . ' FROM Stub:TestEntity filter_param1'
                    . ' INNER JOIN filter_param1.values filter_param1_rel'
                    . ' WHERE filter_param1_rel IN(:param1))',
            ]
        ];
    }

    public function testPrepareData()
    {
        $data = [];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
