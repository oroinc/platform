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
            'name'         => [
                'options'   => ['chart_name' => 11],
                'exception' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'message'   => 'The option "chart_name" with value 11 is expected to be of type "string", '
                    . 'but is of type "integer".'
            ],
            'chart_config' => [
                'options'   => ['chart_name' => 'test', 'chart_config' => 11],
                'exception' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'message'   => 'The option "chart_config" with value 11 is expected to be of type "array", '
                    . 'but is of type "integer".'
            ],
            'empty'        => [
                'options'   => [],
                'exception' => 'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                'message'   => 'The required option "chart_name" is missing.'
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
            ['chart_name' => $chartName],
            ['settings_schema' => $fieldsData]
        );

        $formOptions = ['chart_name' => $chartName];
        if ($useParentOptions) {
            $formOptions['chart_config'] = $chartOptions;
        }

        $configProvider
            ->expects($this->any())
            ->method('getChartConfig')
            ->with($chartName)
            ->will($this->returnValue($chartOptions));

        $type = new ChartSettingsType($configProvider);
        $form = $this->factory->create($type, null, $formOptions);

        foreach (array_keys($fieldsData) as $fieldName) {
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
     *
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
