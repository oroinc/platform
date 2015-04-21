<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormStartType;
use Oro\Bundle\UIBundle\Layout\Block\Extension\FormStartExtension;

class FormStartExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormStartExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new FormStartExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(FormStartType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testSetDefaultOptions($options, $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $this->extension->setDefaultOptions($resolver);
        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    public function optionsDataProvider()
    {
        return [
            [
                [],
                [],
            ],
            [
                ['with_page_parameters' => true],
                ['with_page_parameters' => true],
            ]
        ];
    }

    /**
     * @dataProvider buildViewOptionsDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $view  = new BlockView();
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, $options);
        unset($view->vars['attr']);
        $this->assertEquals($expectedVars, $view->vars);
    }

    public function buildViewOptionsDataProvider()
    {
        return [
            [
                [],
                ['with_page_parameters' => false],
            ],
            [
                ['with_page_parameters' => true],
                ['with_page_parameters' => true],
            ]
        ];
    }
}
