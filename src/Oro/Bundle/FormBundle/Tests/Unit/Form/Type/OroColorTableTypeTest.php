<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroColorTableType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroColorTableTypeTest extends FormIntegrationTestCase
{
    public function testBuildForm()
    {
        $form = $this->factory->create(OroColorTableType::class, [], []);
        $testData = [
            '#FFFFFF',
            '#000000',
        ];
        $form->submit(json_encode($testData));
        $this->assertEquals($testData, $form->getData());
    }

    public function testConfigureOptions()
    {
        $expectedOptions = [
            'picker_control' => null,
        ];

        $form = $this->factory->create(OroColorTableType::class, [], []);
        $form->submit('');
        $options = $form->getConfig()->getOptions();

        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $options
     * @param array $expectedVars
     */
    public function testBuildView(array $options, array $expectedVars)
    {
        $form = $this->factory->create(OroColorTableType::class, [], []);
        $form->submit(json_encode(['#FFFFFF', '#000000']));
        $view = new FormView();
        $view->vars['value'] = [
            '#FFFFFF',
            '#000000',
        ];
        $formType = new OroColorTableType();
        $formType->buildView($view, $form, $options);
        $this->assertEquals($expectedVars, $view->vars);
    }

    public function testGetParent()
    {
        $formType = new OroColorTableType();
        $this->assertEquals(HiddenType::class, $formType->getParent());
    }

    public function testGetName()
    {
        $formType = new OroColorTableType();
        $this->assertEquals('oro_color_table', $formType->getName());
    }

    public function buildViewDataProvider()
    {
        return [
            [
                'options'       => [
                    'picker_control' => true,
                ],
                'expectedVars'  => [
                    'value'     => json_encode(['#FFFFFF','#000000']),
                    'attr'      => [],
                    'configs'   => [
                        'table'     => true,
                        'picker'    => [
                            'control' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
