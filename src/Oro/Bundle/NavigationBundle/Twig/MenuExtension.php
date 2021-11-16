<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Twig\Helper;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render menus and breadcrumbs:
 *   - oro_menu_render
 *   - oro_breadcrumbs
 */
class MenuExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const BREADCRUMBS_TEMPLATE = '@OroNavigation/Menu/breadcrumbs.html.twig';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return Helper
     */
    protected function getMenuHelper()
    {
        return $this->container->get(Helper::class);
    }

    /**
     * @return MenuProviderInterface
     */
    protected function getMenuProvider()
    {
        return $this->container->get(BuilderChainProvider::class);
    }

    /**
     * @return BreadcrumbManagerInterface
     */
    protected function getBreadcrumbManager()
    {
        return $this->container->get(BreadcrumbManagerInterface::class);
    }

    /**
     * @return ConfigurationProvider
     */
    protected function getConfigurationProvider()
    {
        return $this->container->get(ConfigurationProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_menu_render',
                [$this, 'render'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction('oro_menu_get', [$this, 'getMenu']),
            new TwigFunction(
                'oro_breadcrumbs',
                [$this, 'renderBreadCrumbs'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * Renders a menu with the specified renderer.
     *
     * @param ItemInterface|string|array $menu
     * @param array                      $options
     * @param string                     $renderer
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function render($menu, array $options = [], $renderer = null)
    {
        if (!$menu instanceof ItemInterface) {
            $path = [];
            if (is_array($menu)) {
                if (empty($menu)) {
                    throw new \InvalidArgumentException('The array cannot be empty');
                }
                $path = $menu;
                $menu = array_shift($path);
            }

            $menu = $this->getMenu($menu, $path, $options);
        }

        $menu = $this->filterUnallowedItems($menu);
        $menuType = $menu->getExtra('type');
        // rewrite config options with args
        if (!empty($menuType)) {
            $templates = $this->getConfigurationProvider()->getMenuTemplates();
            if (!empty($templates[$menuType])) {
                $options = array_replace_recursive($templates[$menuType], $options);
            }
        }

        return $this->getMenuHelper()->render($menu, $options, $renderer);
    }

    /**
     * Get menu filtered by isAllowed children.
     *
     * @param ItemInterface|array $menu
     * @return ItemInterface|array
     */
    protected function filterUnallowedItems($menu)
    {
        /** @var ItemInterface $item */
        foreach ($menu as $item) {
            if (!$item->hasChildren()) {
                continue;
            }

            $filteredChildren = $this->filterUnallowedItems($item);
            $invisibleChildren = array_filter(
                iterator_to_array($filteredChildren->getIterator()),
                static function (ItemInterface $child) {
                    return !$child->getLabel() || !$child->getExtra('isAllowed') || !$child->isDisplayed();
                }
            );

            if (count($filteredChildren) === count($invisibleChildren)
                && (!$item->getUri() || $item->getUri() === '#')
            ) {
                $item->setExtra('isAllowed', false);
            }
        }

        return $menu;
    }

    /**
     * Render breadcrumbs for menu
     *
     * @param Environment $environment
     * @param string $menuName
     * @param bool $useDecorators
     * @return null|string
     */
    public function renderBreadCrumbs(Environment $environment, $menuName, $useDecorators = true)
    {
        $breadcrumbs = $this->getBreadcrumbManager()->getBreadcrumbs($menuName, $useDecorators);
        if ($breadcrumbs) {
            return $environment->render(
                self::BREADCRUMBS_TEMPLATE,
                [
                    'breadcrumbs' => $breadcrumbs,
                    'useDecorators' => $useDecorators
                ]
            );
        }

        return null;
    }

    /**
     * Retrieves item in the menu, eventually using the menu provider.
     *
     * @param ItemInterface|string $menu
     * @param array                $path
     * @param array                $options
     *
     * @return ItemInterface
     *
     * @throws \InvalidArgumentException when the path is invalid
     */
    public function getMenu($menu, array $path = [], array $options = [])
    {
        if (!$menu instanceof ItemInterface) {
            $menu = $this->getMenuProvider()->get((string) $menu, $options);
        }

        foreach ($path as $child) {
            $menu = $menu->getChild($child);
            if (null === $menu) {
                throw new \InvalidArgumentException(sprintf('The menu has no child named "%s"', $child));
            }
        }

        return $menu;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            BuilderChainProvider::class,
            BreadcrumbManagerInterface::class,
            ConfigurationProvider::class,
            Helper::class
        ];
    }
}
