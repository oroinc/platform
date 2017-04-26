<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Symfony\Component\Form\FormView;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngine;

class TwigRendererEngineTest extends RendererEngineTest
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
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects(self::once())
            ->method('get')
            ->with('oro_layout.debug_block_info')
            ->willReturn(true);

        $this->twigRendererEngine->setConfigManager($configManager);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars['cache_key'] = 'cache_key';
        $template = $this->createMock('\Twig_Template');
        $template->expects($this->once())
            ->method('getTemplateName')
            ->will($this->returnValue('theme'));

        $class = new \ReflectionClass(BaseTwigRendererEngine::class);
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
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('mergeGlobals')
            ->with($result)
            ->will($this->returnValue([$template, 'root']));
        $this->twigRendererEngine->setEnvironment($environment);

        $this->twigRendererEngine->renderBlock($view, [$template, 'root'], 'root', $variables);
    }

    public function createRendererEngine()
    {
        return new TwigRendererEngine();
    }
}
