<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Twig\Helper;
use Knp\Menu\Provider\MenuProviderInterface;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;

class MenuExtension extends \Twig_Extension
{
    const MENU_NAME = 'oro_menu';

    const BREADCRUMBS_TEMPLATE = 'OroNavigationBundle:Menu:breadcrumbs.html.twig';

    /**
     * @var Helper $helper
     */
    private $helper;

    /**
     * @var MenuProviderInterface $provider
     */
    private $provider;

    /**
     * @var array $menuConfiguration
     */
    protected $menuConfiguration;

    /**
     * @var BreadcrumbManagerInterface
     */
    protected $breadcrumbManager;

    /**
     * @param Helper $helper
     * @param MenuProviderInterface $provider
     * @param BreadcrumbManager $breadcrumbManager
     *
     * @deprecated since 1.8 $breadcrumbManager argument will be replaced with BreadcrumbManagerInterface
     * @see \Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface
     */
    public function __construct(
        Helper $helper,
        MenuProviderInterface $provider,
        BreadcrumbManager $breadcrumbManager
    ) {
        $this->helper = $helper;
        $this->provider = $provider;
        $this->breadcrumbManager = $breadcrumbManager;
    }

    /**
     * @param array $configuration
     */
    public function setMenuConfiguration(array $configuration)
    {
        $this->menuConfiguration = $configuration;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'oro_menu_render' => new \Twig_Function_Method($this, 'render', array('is_safe' => array('html'))),
            'oro_menu_get' => new \Twig_Function_Method($this, 'getMenu'),
            'oro_breadcrumbs' => new \Twig_Function_Method(
                $this,
                'renderBreadCrumbs',
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true
                )
            )
        );
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
    public function render($menu, array $options = array(), $renderer = null)
    {
        if (!$menu instanceof ItemInterface) {
            $path = array();
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
        if (!empty($menuType) && !empty($this->menuConfiguration['templates'][$menuType])) {
            $options = array_replace_recursive($this->menuConfiguration['templates'][$menuType], $options);
        }

        return $this->helper->render($menu, $options, $renderer);
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
                    if (!$child->getLabel() || !$child->getExtra('isAllowed')) {
                        $invisibleChildrenCount++;
                    }
                }

                if (count($filteredChildren) == $invisibleChildrenCount
                    && (!$item->getUri() || $item->getUri() == '#')
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
        if ($breadcrumbs = $this->breadcrumbManager->getBreadcrumbs($menuName, $useDecorators)) {
            $template = $environment->loadTemplate(self::BREADCRUMBS_TEMPLATE);

            return $template->render(
                array(
                    'breadcrumbs' => $breadcrumbs,
                    'useDecorators' => $useDecorators
                )
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
    public function getMenu($menu, array $path = array(), array $options = array())
    {
        if (!$menu instanceof ItemInterface) {
            $menu = $this->provider->get((string) $menu, $options);
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
     * @param BreadcrumbManagerInterface $breadcrumbManager
     *
     * @deprecated since 1.8 will be moved to constructor
     */
    public function setBreadcrumbManager(BreadcrumbManagerInterface $breadcrumbManager)
    {
        $this->breadcrumbManager = $breadcrumbManager;
    }
}
