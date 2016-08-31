<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine;

class BaseTwigRendererEngineTest extends RendererEngineTest
{
    /**
     * @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $environment;

    /**
     * @var BaseTwigRendererEngine
     */
    protected $engine;

    protected function setUp()
    {
        $this->environment = $this->getMock('\Twig_Environment', [], [], '', false);

        $this->engine = $this->createRendererEngine();
        $this->engine->setEnvironment($this->environment);
    }

    public function testRenderBlock()
    {
        $cacheKey = '_root_root';

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        /** @var \Twig_Template|\PHPUnit_Framework_MockObject_MockObject $template */
        $template = $this->getMock('\Twig_Template', [], [], '', false);

        $blockName = 'root';
        $variables = ['id' => $blockName];
        $resource = [$template, $blockName];

        $class = new \ReflectionClass(BaseTwigRendererEngine::class);
        $property = $class->getProperty('template');
        $property->setAccessible(true);
        $property->setValue($this->engine, $template);

        $property = $class->getProperty('resources');
        $property->setAccessible(true);
        $property->setValue($this->engine, [$cacheKey => $resource]);

        $this->environment->expects($this->once())
            ->method('mergeGlobals')
            ->with($variables)
            ->will($this->returnValue($variables));

        $template->expects($this->once())
            ->method('displayBlock')
            ->with($blockName, $variables, $resource);

        $this->engine->renderBlock($view, $resource, $blockName, $variables);
    }

    public function testLoadResourcesFromTheme()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $firstTheme = $this->createTheme($blockName);
        $secondTheme = $this->createTheme($blockName);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme, $secondTheme]);

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->willReturn([]);

        $this->assertSame(
            [$secondTheme, $blockName],
            $this->engine->getResourceForBlockName($view, $blockName)
        );
    }

    public function testGetResourceHierarchyLevel()
    {
        $cacheKey = '_main_menu_main_menu';
        $blockName = 'main_menu';
        $blockNameHierarchy = ['block', 'container', $blockName];
        $firstTheme = $this->createTheme('container');
        $secondTheme = $this->createTheme('block');
        $thirdTheme = $this->createTheme($blockName);
        $fourthTheme = $this->createTheme($blockName);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme, $secondTheme, $thirdTheme, $fourthTheme]);

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->willReturn([]);

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 2);
        $this->assertEquals(2, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertEquals(2, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    public function testSwitchToNextParentResource()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $blockNameHierarchy = ['block', $blockName];
        $firstTheme = $this->createTheme($blockName);
        $secondTheme = $this->createTheme('_main_menu');
        $thirdTheme = $this->createTheme($blockName);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme, $secondTheme, $thirdTheme]);

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->willReturn([]);

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->assertSame([$thirdTheme, $blockName], $this->engine->getResourceForBlockName($view, $blockName));

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertSame([$firstTheme, $blockName], $this->engine->getResourceForBlockName($view, $blockName));

        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    public function testSwitchToNextParentResourceForParentBlockType()
    {
        $cacheKey = '_main_menu_main_menu';
        $blockName = 'main_menu';
        $blockNameHierarchy = ['block', 'container', $blockName];
        $firstTheme = $this->createTheme('container');
        $secondTheme = $this->createTheme('block');
        $thirdTheme = $this->createTheme($blockName);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('Symfony\Component\Form\FormView', [], [], '', false);
        $view->vars['cache_key'] = $cacheKey;

        $this->engine->addDefaultThemes([$firstTheme, $secondTheme, $thirdTheme]);

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->willReturn([]);

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 2);
        $this->assertSame([$thirdTheme, $blockName], $this->engine->getResourceForBlockName($view, $blockName));

        $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        $this->assertSame([$firstTheme, 'container'], $this->engine->getResourceForBlockName($view, $blockName));

        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));
    }

    /**
     * @param string $blockName
     * @return \Twig_Template|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTheme($blockName)
    {
        $theme = $this->getMock('\Twig_Template', [], [], '', false);
        $theme->expects($this->any())
            ->method('getBlocks')
            ->willReturn([$blockName => [$theme, $blockName]]);
        $theme->expects($this->any())
            ->method('getParent')
            ->willReturn(false);

        return $theme;
    }

    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        return new BaseTwigRendererEngine();
    }
}
