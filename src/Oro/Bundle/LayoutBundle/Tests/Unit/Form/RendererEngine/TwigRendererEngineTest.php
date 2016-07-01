<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseEngine;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TwigRendererEngine;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

class TwigRendererEngineTest extends RendererEngineTest
{
    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        return new TwigRendererEngine();
    }

    public function testRenderBlock()
    {
        $renderingEngine = $this->createRendererEngine();

        $renderingEngine->setLayoutHelper($this->getMockLayoutHelper());

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
        $property->setValue($renderingEngine, $template);

        $property = $class->getProperty('resources');
        $property->setAccessible(true);
        $property->setValue($renderingEngine, ['cache_key' => []]);

        $variables = ['id' => 'root'];
        $result = array_merge(
            $variables,
            [
                'attr' => [
                    'data-layout-debug-block-id' => 'root',
                    'data-layout-debug-block-template' => 'theme'
                ]
            ]
        );

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $environment */
        $environment = $this->getMock('\Twig_Environment', [], [], '', false);
        $environment->expects($this->once())
            ->method('mergeGlobals')
            ->with($result)
            ->will($this->returnValue([$template, 'root']));
        $renderingEngine->setEnvironment($environment);

        $renderingEngine->renderBlock($view, [$template, 'root'], 'root', $variables);
    }

    public function testSetLayoutHelper()
    {
        $renderingEngine = $this->createRendererEngine();

        $renderingEngine->setLayoutHelper($this->getMockLayoutHelper());

        $class = new \ReflectionClass(TwigRendererEngine::class);
        $property = $class->getProperty('layoutHelper');
        $property->setAccessible(true);
        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Request\LayoutHelper',
            $property->getValue($renderingEngine)
        );
    }

    /**
     * @return LayoutHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLayoutHelper()
    {
        return $this->getMock('Oro\Bundle\LayoutBundle\Request\LayoutHelper', [], [], '', false);
    }
}
