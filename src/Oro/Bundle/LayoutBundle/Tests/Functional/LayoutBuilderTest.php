<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Component\Layout\BlockView;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;

class LayoutBuilderTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testLayoutNestedImportsRendering()
    {
        $expectedRender = <<<HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <div class="base-wrapper">
            <div class="first-wrapper">
            <div class="second-wrapper">
            <div class="third-wrapper">
        </div>
    </div>
    </div>
    </div>
    </body>
</html>
HTML;
        $expectedTree = [
            'head' => [],
            'body' => [
                'wrapper' => [
                    'first_wrapper' => [
                        'first_second_wrapper' => [
                            'first_second_third_wrapper' => []
                        ]
                    ]
                ]
            ]
        ];
        $layout = $this->getLayout('nested_imports');

        $tree = $this->getBlockViewTree($layout->getView());

        $this->assertEquals($expectedTree, $tree);
        $this->assertEquals($expectedRender, $layout->render());
    }

    /**
     * @param BlockView $blockView
     *
     * @return array
     */
    protected function getBlockViewTree(BlockView $blockView)
    {
        $tree = [];
        foreach ($blockView->children as $name => $child) {
            $tree[$name] = $this->getBlockViewTree($child);
        }

        return $tree;
    }

    /**
     * @param string $theme
     *
     * @return Layout
     */
    protected function getLayout($theme)
    {
        CumulativeResourceManager::getInstance()->clear();

        $themeManager = new ThemeManager(
            new ThemeFactory(),
            [
                'base' => [
                    'label' => 'base'
                ],
                $theme => [
                    'parent' => 'base',
                    'label' => $theme
                ]
            ]
        );
        $this->getContainer()
            ->set('oro_layout.theme_manager', $themeManager);

        $resourceProvider = $this->getContainer()
            ->get('oro_layout.theme_extension.resource_provider.theme');

        $resourceProvider->loadResources(new ContainerBuilder(), [
            $this->getResource(__DIR__ . '/../Fixtures/layouts')
        ]);

        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $layoutBuilder = $layoutManager->getLayoutBuilder();
        $layoutBuilder->add('root', null, 'root');

        $context = new LayoutContext();
        $context->set('theme', $theme);

        return $layoutBuilder->getLayout($context);
    }

    /**
     * @param string $path
     *
     * @return CumulativeResourceInfo
     */
    protected function getResource($path)
    {
        $loader = new FolderContentCumulativeLoader('./', -1, false);

        return $loader->load('TestBundle', $path);
    }
}
