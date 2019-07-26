<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Bundle\LayoutBundle\Tests\Fixtures\TestBundle\TestBundle;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractLayoutBuilderTest extends WebTestCase
{
    /** @var array */
    private $initialBundles;

    /** @var ThemeManager */
    private $initialThemeManager;

    /** @var ThemeResourceProvider */
    private $resourcesProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        // prepare test environment
        $container = $this->getContainer();
        $this->initialBundles = CumulativeResourceManager::getInstance()->getBundles();

        $this->resourcesProvider = $container->get('oro_layout.tests.theme_extension.resource_provider.theme');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $container = $this->getContainer();
        CumulativeResourceManager::getInstance()->setBundles($this->initialBundles);

        // clear caches that are changed in getLayout()
        $container->get('oro_layout.cache.block_view_cache')->reset();
        $this->resourcesProvider->warmUpCache();
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
        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $definitions = [
            'base' => [
                'label' => 'base'
            ],
            $theme => [
                'parent' => 'base',
                'label' => $theme
            ]
        ];

        $themeDefinitionBag = $this->createMock(ThemeDefinitionBagInterface::class);
        $themeDefinitionBag->expects($this->any())
            ->method('getThemeNames')
            ->willReturn(array_keys($definitions));
        $themeDefinitionBag->expects($this->any())
            ->method('getThemeDefinition')
            ->willReturnCallback(function ($themeName) use ($definitions) {
                return $definitions[$themeName] ?? null;
            });

        $themeManager = new ThemeManager(
            new ThemeFactory(PropertyAccess::createPropertyAccessor()),
            $themeDefinitionBag
        );
        $this->getContainer()->set('oro_layout.theme_manager.test', $themeManager);

        $this->resourcesProvider->warmUpCache();

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
