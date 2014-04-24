<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;

class ChartSettingsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ChartSettingsType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ChartSettingsType($this->configProvider);

        parent::setUp();
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "chart_name" is  missing.
     */
    public function testRequireOptionsMissing()
    {
        $this->factory->create($this->type, null, array());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "chart_name" with value "11" is expected to be of type "string"
     */
    public function testRequireOptionsIncorrectType()
    {
        $this->factory->create($this->type, null, array(ChartSettingsType::NODE_NAME => 11));
    }

    public function testFieldAdded()
    {
        $expectedName     = 'expectedName';
        $fieldName        = 'firstFieldName';
        $fieldLabel       = 'firstFieldLabel';
        $fieldType        = 'text';
        $fieldNameSecond  = 'firstFieldNameSecond';
        $fieldLabelSecond = 'firstFieldLabelSecond';
        $fieldTypeSecond  = 'integer';
        $settings         = array(
            array(
                'name'    => $fieldName,
                'label'   => $fieldLabel,
                'type'    => $fieldType,
                'options' => array('label' => 'unexpected label', 'required' => true)
            ),
            array(
                'name'    => $fieldNameSecond,
                'label'   => $fieldLabelSecond,
                'type'    => $fieldTypeSecond,
                'options' => array('label' => 'unexpected label', 'required' => false)
            )
        );
        $configs          = array(ChartSettingsType::NODE_SETTINGS => $settings);

        $this->configProvider->expects($this->once())
            ->method('getChartConfig')
            ->with($expectedName)
            ->will($this->returnValue($configs));

        $form = $this->factory->create($this->type, null, array(ChartSettingsType::NODE_NAME => $expectedName));

        $this->assertTrue($form->has($fieldName));
        $this->assertTrue($form->has($fieldNameSecond));

        $actual = $form->get($fieldName);
        $this->assertEquals($actual->getConfig()->getOption('label'), $fieldLabel);
        $this->assertTrue($actual->getConfig()->getOption('required'));
        $this->assertEquals($actual->getConfig()->getType()->getName(), $fieldType);

        $actual = $form->get($fieldNameSecond);
        $this->assertEquals($actual->getConfig()->getOption('label'), $fieldLabelSecond);
        $this->assertFalse($actual->getConfig()->getOption('required'));
        $this->assertEquals($actual->getConfig()->getType()->getName(), $fieldTypeSecond);
    }
}
