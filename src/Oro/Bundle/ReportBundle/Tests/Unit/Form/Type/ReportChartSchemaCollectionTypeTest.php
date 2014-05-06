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
                                    'label'    => 'label',
                                    'name'     => 'name',
                                    'required' => false
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
        $manager = $this
            ->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $schemaCollectionType = new ReportChartSchemaType($manager);
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
