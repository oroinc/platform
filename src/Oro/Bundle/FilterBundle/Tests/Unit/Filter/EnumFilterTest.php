<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class EnumFilterTest extends OrmTestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var EnumFilter */
    protected $filter;

    protected function setUp()
    {
        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');

        /** @var DictionaryApiEntityManager|\PHPUnit\Framework\MockObject\MockObject $dictionaryApiEntityManager */
        $dictionaryApiEntityManager =
            $this->getMockBuilder('Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->filter = new EnumFilter($this->formFactory, new FilterUtility(), $dictionaryApiEntityManager);
    }

    public function testInit()
    {
        $params = [];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => []
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithNullValue()
    {
        $params = [
            'null_value' => ':empty:'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'null_value'                     => ':empty:',
                'options'                        => []
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithClass()
    {
        $params = [
            'class' => 'Test\EnumValue'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => [
                    'class' => 'Test\EnumValue'
                ]
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithEnumCode()
    {
        $params = [
            'enum_code' => 'test_enum'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => [
                    'enum_code' => 'test_enum',
                    'class' => 'Extend\Entity\EV_Test_Enum'
                ],
                'class' => 'Extend\Entity\EV_Test_Enum'
            ],
            'params',
            $this->filter
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(EnumFilterType::class)
            ->will($this->returnValue($form));

        $this->assertSame(
            $form,
            $this->filter->getForm()
        );
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

        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->setMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $fieldName     = 'o.testField';
        $parameterName = 'param1';

        $reflection = new \ReflectionObject($this->filter);
        $method     = $reflection->getMethod('buildComparisonExpr');
        $method->setAccessible(true);
        $expr = $method->invokeArgs($this->filter, [$ds, $filterType, $fieldName, $parameterName]);

        $qb->where($expr);
        $result = $qb->getDQL();

        $this->assertSame(
            $expected,
            $result
        );
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
