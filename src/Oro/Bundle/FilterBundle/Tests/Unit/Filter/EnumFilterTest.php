<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class EnumFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var EnumFilter */
    protected $filter;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

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
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(EnumFilterType::NAME)
            ->will($this->returnValue($form));

        $this->assertSame(
            $form,
            $this->filter->getForm()
        );
    }
}
