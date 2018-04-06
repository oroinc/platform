<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class AbstractLayoutBuilderTest extends WebTestCase
{
    /**
     * @var ThemeManager
     */
    private $oldThemeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->oldThemeManager = $this->getContainer()->get('oro_layout.theme_manager');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $container = $this->getContainer();
        // Revert overridden service
        $container->set('oro_layout.theme_manager', $this->oldThemeManager);

        // Clear caches that are changed in getLayout()
        $container->get('oro_layout.cache.block_view_cache')->reset();
        $container->get('oro_layout.theme_extension.resource_provider.cache')->deleteAll();
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
