<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroChoiceTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $formType = new OroChoiceType();
        $this->assertEquals(Select2ChoiceType::class, $formType->getParent());
    }

    public function testGetName()
    {
        $formType = new OroChoiceType();
        $this->assertEquals('oro_choice', $formType->getName());
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
        $form = $this->factory->create(OroChoiceType::class, $data, $options);
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
                    'choices' => ['c1', 'c2', 'c3']
                ]
            ],
        ];
    }
}
