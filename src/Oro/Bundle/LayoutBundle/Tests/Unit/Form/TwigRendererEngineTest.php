<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseEngine;

use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Form\TwigRendererEngine;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

class TwigRendererEngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwigRendererEngine
     */
    protected $twigRendererEngine;

    protected function setUp()
    {
        $this->twigRendererEngine = new TwigRendererEngine();
    }

    public function testRenderBlock()
    {
        $layoutHelper = $this->getMockLayoutHelper();
        $layoutHelper->expects($this->once())
            ->method('isProfilerEnabled')
            ->will($this->returnValue(true));

        $this->twigRendererEngine->setLayoutHelper($layoutHelper);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = 'cache_key';
        $template = $this->getMock('\Twig_Template', [], [], '', false);
        $template->expects($this->once())
            ->method('getTemplateName')
            ->will($this->returnValue('theme'));

        $class = new \ReflectionClass(BaseEngine::class);
        $property = $class->getProperty('template');
        $property->setAccessible(true);
        $property->setValue($this->twigRendererEngine, $template);

        $property = $class->getProperty('resources');
        $property->setAccessible(true);
        $property->setValue($this->twigRendererEngine, ['cache_key' => []]);

        $variables = ['id' => 'root'];
        $result = array_merge(
            $variables,
            [
                'attr' => [
                    'data-layout-debug-block-id'        => 'root',
                    'data-layout-debug-block-template'  => 'theme'
                ]
            ]
        );

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $environment */
        $environment = $this->getMock('\Twig_Environment', [], [], '', false);
        $environment->expects($this->once())
            ->method('mergeGlobals')
            ->with($result)
            ->will($this->returnValue([$template, 'root']));
        $this->twigRendererEngine->setEnvironment($environment);

        $this->twigRendererEngine->renderBlock($view, [$template, 'root'], 'root', $variables);
    }

    /**
     * @return LayoutHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLayoutHelper()
    {
        return $this->getMock('Oro\Bundle\LayoutBundle\Request\LayoutHelper', [], [], '', false);
    }
}
