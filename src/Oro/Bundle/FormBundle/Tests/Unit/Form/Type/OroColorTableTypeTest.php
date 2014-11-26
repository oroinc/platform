<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroColorTableType;

class OroColorTableTypeTest extends FormIntegrationTestCase
{
    /** @var OroColorTableType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OroColorTableType();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType, [], []);
        $testData = [
            '#FFFFFF',
            '#000000',
        ];
        $form->submit(json_encode($testData));
        $this->assertEquals($testData, $form->getData());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = [
            'picker_control' => null,
        ];

        $form = $this->factory->create($this->formType, [], []);
        $form->submit('');
        $options = $form->getConfig()->getOptions();

        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $form = $this->factory->create($this->formType, [], []);
        $form->submit(json_encode(['#FFFFFF', '#000000']));
        $view = new FormView();
        $view->vars['value'] = [
            '#FFFFFF',
            '#000000',
        ];
        $this->formType->buildView($view, $form, $options);
        $this->assertEquals($expectedVars, $view->vars);
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_color_table', $this->formType->getName());
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
