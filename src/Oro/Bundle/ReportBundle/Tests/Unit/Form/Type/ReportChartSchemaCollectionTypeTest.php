<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaCollectionType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ReportChartSchemaCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ReportChartSchemaCollectionType
     */
    protected $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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
        $this->factory->create(ReportChartSchemaCollectionType::class, null, []);
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

        $translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $schemaCollectionType = new ReportChartSchemaType($manager);
        $fieldChoiceType      = new FieldChoiceType($translator);

        return [
            new PreloadedExtension(
                [
                    ReportChartSchemaCollectionType::class => $this->type,
                    ReportChartSchemaType::class => $schemaCollectionType,
                    FieldChoiceType::class => $fieldChoiceType
                ],
                []
            )
        ];
    }
}
