<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorChoiceType;

class OroSimpleColorChoiceTypeTest extends FormIntegrationTestCase
{
    /** @var OroSimpleColorChoiceType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(['#FFFFFF', '#000000']));

        $this->formType = new OroSimpleColorChoiceType($configManager);
    }

    public function testSetDefaultOptionsWithCustomColorSchema()
    {
        $resolver = $this->getOptionsResolver();
        $this->formType->setDefaultOptions($resolver);

        $options = [
            'color_schema'  => 'custom',
            'choices'       => [
                '#FFFFFF',
                '#000000',
            ],
        ];

        $resolvedOptions = $resolver->resolve($options);

        $this->assertEquals(
            [
                'choices'           => [
                    '#FFFFFF',
                    '#000000',
                ],
                'translatable'      => false,
                'allow_empty_color' => false,
                'empty_color'       => null,
                'picker'            => false,
                'picker_delay'      => 0,
                'color_schema'      => 'custom',

            ],
            $resolvedOptions
        );
    }

    public function testSetDefaultOptionsWithStoredColorSchema()
    {
        $resolver = $this->getOptionsResolver();
        $this->formType->setDefaultOptions($resolver);

        $options = [
            'color_schema' => 'stored',
        ];

        $resolvedOptions = $resolver->resolve($options);

        $this->assertEquals(
            [
                'choices'           => [
                    '#FFFFFF' => '#FFFFFF',
                    '#000000' => '#000000',
                ],
                'translatable'      => false,
                'allow_empty_color' => false,
                'empty_color'       => null,
                'picker'            => false,
                'picker_delay'      => 0,
                'color_schema'      => 'stored',

            ],
            $resolvedOptions
        );
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $this->formType->buildView($view, $form, $options);

        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }

        $this->assertArrayHasKey('attr', $view->vars);
        $this->assertArrayHasKey('class', $view->vars['attr']);
        $this->assertEquals('no-input-widget', $view->vars['attr']['class']);
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_simple_color_choice', $this->formType->getName());
    }

    public function buildViewDataProvider()
    {
        return [
            [
                'options' => [
                    'translatable'      => false,
                    'allow_empty_color' => false,
                    'empty_color'       => false,
                    'picker'            => false,
                ],
                'expectedVars' => [
                    'translatable'      => false,
                    'allow_empty_color' => false,
                    'empty_color'       => false,
                ],
            ],
            [
                'options' => [
                    'translatable'      => true,
                    'allow_empty_color' => true,
                    'empty_color'       => true,
                    'picker'            => false,
                ],
                'expectedVars' => [
                    'translatable'      => true,
                    'allow_empty_color' => true,
                    'empty_color'       => true,
                ],
            ],
        ];
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([]);

        return $resolver;
    }
}
