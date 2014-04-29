<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaType;

class ReportChartSchemaTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ReportChartSchemaType
     */
    protected $type;

    protected function setUp()
    {
        $manager = $this
            ->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ReportChartSchemaType($manager);

        parent::setUp();
    }

    /**
     * @param array $dataSchema
     *
     * @dataProvider dataSchemaProvider
     */
    public function testBuildForm(array $dataSchema)
    {
        $this->factory->create($this->type, null, ['data_schema' => $dataSchema]);
    }

    /**
     * @return array
     */
    public function dataSchemaProvider()
    {
        return [
            'full' => [
                'dataSchema' => [
                    'fieldName' => [
                        'label'    => 'label',
                        'name'     => 'name',
                        'required' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $fieldChoiceType = new FieldChoiceType();

        return [
            new PreloadedExtension(
                [
                    $fieldChoiceType->getName() => $fieldChoiceType
                ],
                []
            )
        ];
    }
}
