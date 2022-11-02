<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngine;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Template;

class TwigRendererEngineTest extends RendererEngineTest
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var TwigRendererEngine */
    private $twigRendererEngine;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->twigRendererEngine = $this->createRendererEngine();
    }

    public function testRenderBlock()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::once())
            ->method('get')
            ->with('oro_layout.debug_block_info')
            ->willReturn(true);

        $this->twigRendererEngine->setConfigManager($configManager);

        $view = $this->createMock(FormView::class);
        $view->vars['cache_key'] = 'cache_key';
        $template = $this->createMock(Template::class);
        $template->expects($this->once())
            ->method('getTemplateName')
            ->willReturn('theme');

        ReflectionUtil::setPropertyValue($this->twigRendererEngine, 'template', $template);
        ReflectionUtil::setPropertyValue($this->twigRendererEngine, 'resources', ['cache_key' => []]);

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
            ->willReturn([$template, 'root']);

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
