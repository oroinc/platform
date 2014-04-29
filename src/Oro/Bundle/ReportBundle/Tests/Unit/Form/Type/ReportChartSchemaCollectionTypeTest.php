<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaCollectionType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;

class ReportChartSchemaCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ReportChartSchemaCollectionType
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

        $this->configProvider
            ->expects($this->once())
            ->method('getChartConfigs')
            ->will(
                $this->returnValue(
                    [
                        'line_chart' => [
                            'data_schema' => [
                                [
                                    'label' => 'label',
                                    'name'  => 'name'
                                ]
                            ]
                        ]
                    ]
                )
            );

        $this->type = new ReportChartSchemaCollectionType($this->configProvider);

        parent::setUp();
    }

    public function testBuildForm()
    {
        $this->factory->create($this->type, null, []);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $schemaCollectionType = new ReportChartSchemaType();
        $fieldChoiceType      = new FieldChoiceType();

        return [
            new PreloadedExtension(
                [
                    $schemaCollectionType->getName() => $schemaCollectionType,
                    $fieldChoiceType->getName()      => $fieldChoiceType
                ],
                []
            )
        ];
    }
}
