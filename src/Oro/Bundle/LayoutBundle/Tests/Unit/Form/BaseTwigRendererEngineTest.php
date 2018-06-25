<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form;

use Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class BaseTwigRendererEngineTest extends RendererEngineTest
{
    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $environment;

    /**
     * @var BaseTwigRendererEngine
     */
    protected $engine;

    protected function setUp()
    {
        $this->environment = $this->createMock(Environment::class);
        $this->engine = $this->createRendererEngine();
    }

    public function testRenderBlock()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $variables = ['id' => $blockName];
        $template = $this->createTheme($blockName);
        $resource = [$template, $blockName];

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars['cache_key'] = $cacheKey;

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->will($this->returnValue($variables));

        $template->expects($this->once())
            ->method('displayBlock')
            ->with($blockName, $variables, [$blockName => $resource]);

        $this->engine->setTheme($view, [$template]);
        $this->engine->getResourceForBlockName($view, $blockName);
        $this->engine->renderBlock($view, $resource, $blockName, $variables);
    }

    public function testLoadResourcesFromTheme()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $firstTheme = $this->createTheme($blockName);
        $secondTheme = $this->createTheme($blockName);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
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
        $blockNameHierarchy = [
            'block',
            'container',
            'datagrid',
            '__datagrid__datagrid',
        ];

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars['cache_key'] = '_customer_role_datagrid';

        $this->engine->addDefaultThemes([
            $this->createTheme('block'),
            $this->createTheme('container'),
            $this->createTheme('datagrid'),
            $this->createTheme('__datagrid__datagrid'),
            $this->createTheme('__datagrid__datagrid'),
        ]);

        $this->environment->expects($this->any())
            ->method('mergeGlobals')
            ->willReturn([]);

        //switch to next parent resource on the same hierarchy level
        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 3);
        $this->assertEquals(3, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 3));

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 3);
        $this->engine->switchToNextParentResource($view, $blockNameHierarchy, 3);
        $this->assertEquals(3, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 3));

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 2);
        $this->assertEquals(2, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 2));

        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->assertEquals(1, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));

        //switch to next parent resource on the previous hierarchy level
        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 1);
        $this->engine->switchToNextParentResource($view, $blockNameHierarchy, 1);
        $this->assertEquals(0, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    /**
     * @param string $blockName
     * @return \Twig_Template|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createTheme($blockName)
    {
        $theme = $this->createMock('\Twig_Template');
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
        return new BaseTwigRendererEngine([], $this->environment);
    }
}
