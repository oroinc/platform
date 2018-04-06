<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

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
        $this->factory->create(ReportChartSchemaType::class, null, ['data_schema' => $dataSchema]);
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
                        'required' => true,
                        'default_type' => 'string',
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
        $translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fieldChoiceType = new FieldChoiceType($translator);

        return [
            new PreloadedExtension(
                [
                    ReportChartSchemaType::class => $this->type,
                    $fieldChoiceType->getName() => $fieldChoiceType
                ],
                []
            )
        ];
    }
}
