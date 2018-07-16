<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TemplatingRendererEngine;
use Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngineTest;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormView;

class TemplatingRendererEngineTest extends RendererEngineTest
{
    /**
     * @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $phpEngine;

    /**
     * @var TemplatingRendererEngine
     */
    protected $engine;

    protected function setUp()
    {
        /** @var \Symfony\Component\Templating\EngineInterface $templatingEngine */
        $this->phpEngine = $this->createMock('Symfony\Component\Templating\EngineInterface');
        $this->engine = new TemplatingRendererEngine($this->phpEngine);
    }

    public function testLoadResourcesFromTheme()
    {
        $cacheKey = '_root_root';
        $blockName = 'root';
        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
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
        $blockNameHierarchy = [
            'block',
            'container',
            'datagrid',
            '__datagrid__datagrid',
        ];

        $firstTheme = 'MyBundle:layouts/first_theme/php';
        $secondTheme = 'MyBundle:layouts/second_theme/php';

        $this->engine->addDefaultThemes([
            $firstTheme,
            $secondTheme,
        ]);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars['cache_key'] = '_customer_role_datagrid';

        $this->phpEngine->expects($this->any())
            ->method('exists')
            ->will($this->returnCallback(function ($path) use ($firstTheme, $secondTheme) {
                switch ($path) {
                    case $firstTheme.':block.html.php':
                    case $firstTheme.':container.html.php':
                    case $firstTheme.':datagrid.html.php':
                    case $firstTheme.':__datagrid__datagrid.html.php':
                    case $secondTheme.'__datagrid__datagrid.html.php':
                        return true;
                    default:
                        return false;
                }
            }));

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
        $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, 0);
        $this->engine->switchToNextParentResource($view, $blockNameHierarchy, 1);
        $this->assertEquals(0, $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, 1));
    }

    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        /** @var \Symfony\Component\Templating\EngineInterface $phpEngine */
        $phpEngine = $this->createMock('Symfony\Component\Templating\EngineInterface');
        $engine = new TemplatingRendererEngine($phpEngine);

        return $engine;
    }
}
