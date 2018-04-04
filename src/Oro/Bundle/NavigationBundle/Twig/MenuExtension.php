<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Twig\Helper;
use Oro\Bundle\NavigationBundle\Config\MenuConfiguration;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for menu & breadcrumbs rendering
 */
class MenuExtension extends \Twig_Extension
{
    const MENU_NAME = 'oro_menu';

    const BREADCRUMBS_TEMPLATE = 'OroNavigationBundle:Menu:breadcrumbs.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return Helper
     */
    protected function getMenuHelper()
    {
        return $this->container->get('knp_menu.helper');
    }

    /**
     * @return MenuProviderInterface
     */
    protected function getMenuProvider()
    {
        return $this->container->get('oro_menu.builder_chain');
    }

    /**
     * @return BreadcrumbManagerInterface
     */
    protected function getBreadcrumbManager()
    {
        return $this->container->get('oro_navigation.chain_breadcrumb_manager');
    }

    /**
     * @return MenuConfiguration
     */
    protected function getMenuConfiguration()
    {
        return $this->container->get('oro_menu.configuration');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_menu_render',
                [$this, 'render'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction('oro_menu_get', [$this, 'getMenu']),
            new \Twig_SimpleFunction(
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
            $templates = $this->getMenuConfiguration()->getTemplates();
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
            if ($item->hasChildren()) {
                $filteredChildren = $this->filterUnallowedItems($item);
                $invisibleChildrenCount = 0;
                /** @var ItemInterface $child */
                foreach ($filteredChildren as $child) {
                    if (!$child->getLabel() || !$child->getExtra('isAllowed') || !$child->isDisplayed()) {
                        $invisibleChildrenCount++;
                    }
                }

                if (count($filteredChildren) === $invisibleChildrenCount
                    && (!$item->getUri() || $item->getUri() === '#')
                ) {
                    $item->setExtra('isAllowed', false);
                }
            }
        }

        return $menu;
    }

    /**
     * Render breadcrumbs for menu
     *
     * @param \Twig_Environment $environment
     * @param string $menuName
     * @param bool $useDecorators
     * @return null|string
     */
    public function renderBreadCrumbs(\Twig_Environment $environment, $menuName, $useDecorators = true)
    {
        $breadcrumbs = $this->getBreadcrumbManager()->getBreadcrumbs($menuName, $useDecorators);
        if ($breadcrumbs) {
            $template = $environment->loadTemplate(self::BREADCRUMBS_TEMPLATE);

            return $template->render(
                [
                    'breadcrumbs' => $breadcrumbs,
                    'useDecorators' => $useDecorators
                ]
            );
        }

        return null;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return self::MENU_NAME;
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
}
