<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tests\Unit\Twig\Template\TestHTML;
use Oro\Bundle\UIBundle\Twig\BlockExtension;

class BlockExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BlockExtension
     */
    private $twigExtension;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->twigExtension = new BlockExtension();
    }

    public function testName()
    {
        $this->assertEquals('oro_ui.block', $this->twigExtension->getName());
    }

    public function testRenderBlock()
    {
        $testTemplate = 'testTemplate';
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = new TestHTML($environment);
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->with($testTemplate)
            ->will($this->returnValue($template));

        $this->twigExtension->renderBlock($environment, [], 'testTemplate', 'block');
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('render_block', $function->getName());
        $this->assertEquals([$this->twigExtension, 'renderBlock'], $function->getCallable());
    }
}
