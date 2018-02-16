<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroChoiceType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new OroChoiceType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_choice', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_select2_choice', $this->formType->getParent());
    }

    /**
     * @param float $data
     * @param array $viewData
     * @param array $options
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        $data,
        array $viewData,
        array $options = []
    ) {
        $form = $this->factory->create($this->formType, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertSame($value, $view->vars[$key]);
        }
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return [
            'empty' => [
                'data'     => '',
                'viewData' => [
                    'value' => ''
                ],
            ],
            'select one choice' => [
                'data'     => 'c1',
                'viewData' => [
                    'value' => 'c1'
                ],
                'options' => [
                    'choices' => ['c1', 'c2', 'c3'],
                    'choices_as_values' => true,
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_select2_choice' => new Select2Type(
                        'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                        'oro_select2_choice'
                    )
                ],
                []
            )
        ];
    }
}
