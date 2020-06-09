<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class EnumFilterTest extends OrmTestCase
{
    /** @var FormFactoryInterface|MockObject */
    protected $formFactory;

    /** @var EnumFilter */
    protected $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        /** @var DictionaryApiEntityManager|MockObject $dictionaryApiEntityManager */
        $dictionaryApiEntityManager = $this->getMockBuilder(DictionaryApiEntityManager::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->filter = new class(
            $this->formFactory,
            new FilterUtility(),
            $dictionaryApiEntityManager
        ) extends EnumFilter {
            public function xgetParams(): array
            {
                return $this->params;
            }

            public function xbuildComparisonExpr(
                FilterDatasourceAdapterInterface $ds,
                $comparisonType,
                $fieldName,
                $parameterName
            ) {
                return parent::buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
            }
        };
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => []],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithNullValue()
    {
        $this->filter->init('test', ['null_value' => ':empty:']);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'null_value' => ':empty:', 'options' => []],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithClass()
    {
        $this->filter->init('test', ['class' => 'Test\EnumValue']);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['class' => 'Test\EnumValue']],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithEnumCode()
    {
        $this->filter->init('test', ['enum_code' => 'test_enum']);
        static::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options' => [
                    'enum_code' => 'test_enum',
                    'class' => 'Extend\Entity\EV_Test_Enum'
                ],
                'class' => 'Extend\Entity\EV_Test_Enum'
            ],
            $this->filter->xgetParams()
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(EnumFilterType::class)
            ->willReturn($form);

        static::assertSame($form, $this->filter->getForm());
    }

    /**
     * @dataProvider filterProvider
     *
     * @param int    $filterType
     * @param string $expected
     */
    public function testBuildComparisonExpr($filterType, $expected)
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestOrder', 'o');

        /** @var OrmFilterDatasourceAdapter|MockObject $ds */
        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$qb])
            ->getMock();

        $fieldName     = 'o.testField';
        $parameterName = 'param1';

        $expr = $this->filter->xbuildComparisonExpr($ds, $filterType, $fieldName, $parameterName);

        $qb->where($expr);
        $result = $qb->getDQL();

        static::assertSame($expected, $result);
    }

    public function filterProvider()
    {
        return [
            [
                DictionaryFilterType::TYPE_NOT_IN,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IS NULL OR o.testField NOT IN(:param1)'
            ],
            [
                DictionaryFilterType::EQUAL,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField = :param1'
            ],
            [
                DictionaryFilterType::NOT_EQUAL,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IS NULL OR o.testField <> :param1'
            ],
            [
                DictionaryFilterType::TYPE_IN,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IN(:param1)'
            ],
        ];
    }
}
