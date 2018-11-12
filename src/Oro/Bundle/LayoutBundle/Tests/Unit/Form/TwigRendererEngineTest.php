<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngine;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class TwigRendererEngineTest extends RendererEngineTest
{
    /**
     * @var TwigRendererEngine
     */
    protected $twigRendererEngine;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $environment;

    protected function setUp()
    {
        $this->environment = $this->createMock(Environment::class);
        $this->twigRendererEngine = $this->createRendererEngine();
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

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
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

        $this->environment->expects($this->once())
            ->method('mergeGlobals')
            ->with($result)
            ->will($this->returnValue([$template, 'root']));

        $this->twigRendererEngine->renderBlock($view, [$template, 'root'], 'root', $variables);
    }

    /**
     * @return TwigRendererEngine
     */
    public function createRendererEngine()
    {
        return new TwigRendererEngine([], $this->environment);
    }
}
