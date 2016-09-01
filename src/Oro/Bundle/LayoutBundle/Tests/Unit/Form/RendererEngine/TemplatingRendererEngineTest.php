<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Symfony\Component\Form\FormView;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TemplatingRendererEngine;
use Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngineTest;

class TemplatingRendererEngineTest extends RendererEngineTest
{
    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $phpEngine;

    /**
     * @var TemplatingRendererEngine
     */
    protected $engine;

    protected function setUp()
    {
        /** @var \Symfony\Component\Templating\EngineInterface $templatingEngine */
        $this->phpEngine = $this->getMock('Symfony\Component\Templating\EngineInterface');
        $this->engine = new TemplatingRendererEngine($this->phpEngine);
    }

    public function testLoadResourcesFromTheme()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme]);
        $this->engine->setTheme($view, [$secondTheme]);

        $this->phpEngine->expects($this->any())
            ->method('exists')
            ->will($this->returnCallback(function ($path) use ($firstTheme) {
                switch ($path) {
                    case $firstTheme . ':root.html.php':
                        return true;
                    default:
                        return false;
                }
            }))
        ;

        $this->assertSame(
            $firstTheme . ':root.html.php',
            $this->engine->getResourceForBlockName($view, $blockName)
        );
    }

    public function testGetResourceHierarchyLevel()
    {
        $cacheKey = '_main_menu_main_menu';
        $blockName = 'main_menu';
        $blockNameHierarchy = ['block', 'container', $blockName];
        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';
        $thirdTheme = 'MyBundle:layouts/third_theme/php';

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme]);
        $this->engine->setTheme($view, [$secondTheme, $thirdTheme]);

        $this->phpEngine->expects($this->any())
            ->method('exists')
            ->will($this->returnCallback(function ($path) use ($firstTheme, $secondTheme, $thirdTheme) {
                switch ($path) {
                    case $firstTheme . ':container.html.php':
                        return true;
                    case $secondTheme . ':main_menu.html.php':
                        return true;
                    case $thirdTheme . ':main_menu.html.php':
                        return true;
                    default:
                        return false;
                }
            }))
        ;

        $this->engine->getResourceForBlockName($view, 'container');
        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 2);
        $this->assertEquals(2, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertEquals(2, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    public function testSwitchToNextParentResource()
    {
        $cacheKey = '_main_menu_main_menu';
        $blockName = 'main_menu';
        $blockNameHierarchy = ['container', $blockName];
        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';
        $thirdTheme = 'MyBundle:layouts/third_theme/php';

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme]);
        $this->engine->setTheme($view, [$secondTheme, $thirdTheme]);

        $this->phpEngine->expects($this->any())
            ->method('exists')
            ->will($this->returnCallback(function ($path) use ($firstTheme, $secondTheme, $thirdTheme) {
                switch ($path) {
                    case $firstTheme . ':main_menu.html.php':
                        return true;
                    case $thirdTheme . ':main_menu.html.php':
                        return true;
                    default:
                        return false;
                }
            }))
        ;

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->assertSame(
            $thirdTheme . ':main_menu.html.php',
            $this->engine->getResourceForBlockName($view, $blockName)
        );

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertSame(
            $firstTheme . ':main_menu.html.php',
            $this->engine->getResourceForBlockName($view, $blockName)
        );

        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    public function testSwitchToNextParentResourceForParentBlockType()
    {
        $cacheKey = '_main_menu_main_menu';
        $blockName = 'main_menu';
        $blockNameHierarchy = ['container', $blockName];
        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';
        $thirdTheme = 'MyBundle:layouts/third_theme/php';

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $rootView = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $rootView->vars['cache_key'] = '_root_root';

        $this->engine->addDefaultThemes([$firstTheme]);
        $this->engine->setTheme($view, [$secondTheme, $thirdTheme]);

        $this->phpEngine->expects($this->any())
            ->method('exists')
            ->will($this->returnCallback(function ($path) use ($firstTheme, $secondTheme, $thirdTheme) {
                switch ($path) {
                    case $firstTheme . ':container.html.php':
                        return true;
                    case $thirdTheme . ':main_menu.html.php':
                        return true;
                    default:
                        return false;
                }
            }));

        $this->engine->getResourceForBlockName($view, 'container');
        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->assertSame(
            $thirdTheme . ':main_menu.html.php',
            $this->engine->getResourceForBlockName($view, $blockName)
        );

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertSame(
            $firstTheme . ':container.html.php',
            $this->engine->getResourceForBlockName($view, $blockName)
        );

        $this->assertEquals(0, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        /** @var \Symfony\Component\Templating\EngineInterface $phpEngine */
        $phpEngine = $this->getMock('Symfony\Component\Templating\EngineInterface');
        $engine = new TemplatingRendererEngine($phpEngine);

        return $engine;
    }
}
