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
        $this->configProvider = $this
            ->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ChartSettingsType($this->configProvider);

        parent::setUp();
    }

    /**
     * @param array  $options
     * @param string $exception
     * @param string $message
     *
     * @dataProvider invalidOptionsProvider
     */
    public function testRequireOptionsIncorrectType(array $options, $exception, $message)
    {
        $this->setExpectedException(
            $exception,
            $message
        );

        $this->factory->create($this->type, null, $options);
    }

    public function invalidOptionsProvider()
    {
        return [
            'name'          => [
                'options'   => [ChartSettingsType::NAME => 11],
                'exception' => '\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'message'   => 'The option "name" with value "11" is expected to be of type "string"'
            ],
            'chart_options' => [
                'options'   => [ChartSettingsType::CHART_OPTIONS => 11],
                'exception' => '\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'message'   => 'The option "chart_options" with value "11" is expected to be of type "array"'
            ],
            'empty'         => [
                'options'   => [],
                'exception' => '\Oro\Bundle\ChartBundle\Exception\InvalidArgumentException',
                'message'   => 'Missing options for'
            ]
        ];
    }

    /**
     * @param array   $fieldsData
     * @param string  $chartName
     * @param boolean $useParentOptions
     *
     * @dataProvider fieldDataProvider
     */
    public function testFieldAdded(array $fieldsData, $chartName, $useParentOptions)
    {
        $configProvider = $this
            ->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $chartOptions = array_merge(
            [ChartSettingsType::NAME => $chartName],
            [ChartSettingsType::SETTINGS_SCHEMA => $fieldsData]
        );

        $formOptions = [ChartSettingsType::NAME => $chartName];
        if ($useParentOptions) {
            $formOptions[ChartSettingsType::CHART_OPTIONS] = $chartOptions;
        }

        $configProvider
            ->expects($this->any())
            ->method('getChartConfig')
            ->with($chartName)
            ->will($this->returnValue($chartOptions));

        $type = new ChartSettingsType($configProvider);
        $form = $this->factory->create($type, null, $formOptions);

        foreach ($fieldsData as $fieldName => $fieldData) {
            $this->assertTrue($form->has($fieldName . 'Name'));
            $actual = $form->get($fieldName . 'Name');
            $this->assertEquals($actual->getConfig()->getOption('label'), $fieldName . 'Label');
        }
    }

    public function fieldDataProvider()
    {
        return [
            'name'    => [
                'fieldsData'       => [
                    'first'  => $this->getFieldData('first'),
                    'second' => $this->getFieldData('second')
                ],
                'chartName'        => 'chart_name',
                'useParentOptions' => false
            ],
            'options' => [
                'fieldsData'       => [
                    'first'  => $this->getFieldData('first'),
                    'second' => $this->getFieldData('second')
                ],
                'chartName'        => 'chart_name',
                'useParentOptions' => true
            ]
        ];
    }

    /**
     * @param string $fieldName
     * @return array
     */
    protected function getFieldData($fieldName)
    {
        return [
            'name'    => $fieldName . 'Name',
            'label'   => $fieldName . 'Label',
            'type'    => 'text',
            'options' => [
                'label'    => $fieldName . 'NewLabel',
                'required' => false
            ]
        ];
    }
}
