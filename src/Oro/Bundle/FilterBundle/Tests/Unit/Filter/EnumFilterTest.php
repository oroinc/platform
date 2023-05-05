<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class EnumFilterTest extends OrmTestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var EnumFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $dictionaryApiEntityManager = $this->createMock(DictionaryApiEntityManager::class);

        $this->filter = new EnumFilter(
            $this->formFactory,
            new FilterUtility(),
            $dictionaryApiEntityManager
        );
    }

    public function testInit()
    {
        $this->filter->init('test', []);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => []],
            $params
        );
    }

    public function testInitWithNullValue()
    {
        $this->filter->init('test', ['null_value' => ':empty:']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'null_value' => ':empty:', 'options' => []],
            $params
        );
    }

    public function testInitWithClass()
    {
        $this->filter->init('test', ['class' => 'Test\EnumValue']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['class' => 'Test\EnumValue']],
            $params
        );
    }

    public function testInitWithEnumCode()
    {
        $this->filter->init('test', ['enum_code' => 'test_enum']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options' => [
                    'enum_code' => 'test_enum',
                    'class' => 'Extend\Entity\EV_Test_Enum'
                ],
                'class' => 'Extend\Entity\EV_Test_Enum'
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
     * @dataProvider filterProvider
     */
    public function testBuildComparisonExpr(int $filterType, string $expected)
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestOrder', 'o');

        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$qb])
            ->getMock();

        $fieldName = 'o.testField';
        $parameterName = 'param1';

        $expr = ReflectionUtil::callMethod(
            $this->filter,
            'buildComparisonExpr',
            [$ds, $filterType, $fieldName, $parameterName]
        );

        $qb->where($expr);
        $result = $qb->getDQL();

        self::assertSame($expected, $result);
    }

    public function filterProvider(): array
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

    public function testPrepareData()
    {
        $data = [];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
