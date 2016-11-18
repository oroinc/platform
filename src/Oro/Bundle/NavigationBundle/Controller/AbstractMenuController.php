<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;

abstract class AbstractMenuController extends Controller
{
    /**
     * @return String
     */
    abstract protected function getScopeType();

    /**
     * @return MenuUpdateManager
     */
    abstract protected function getManager();

    /**
     * @return array
     */
    abstract protected function getContextForMenuUpdateBuilder();

    /**
     * @throws AccessDeniedException
     */
    abstract protected function checkAcl();

    /**
     * @return array
     */
    protected function index()
    {
        $this->checkAcl();

        return [
            'entityClass' => MenuUpdate::class
        ];
    }

    /**
     * @param string $menuName
     * @param array  $context
     * @return array
     */
    protected function view($menuName, array $context)
    {
        $this->checkAcl();

        $menu = $this->getMenu($menuName);

        return [
            'entity' => $menu,
            'scope' => $this->getScope($context),
            'tree' => $this->createMenuTree($menu)
        ];
    }

    /**
     * @param string $menuName
     * @param string $parentKey
     * @param array  $context
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, array $context)
    {
        $this->checkAcl();

        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate(
            $context,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'custom' => true
            ]
        );

        return $this->handleUpdate($menuUpdate, $context);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param array   $context
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, array $context)
    {
        $this->checkAcl();

        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope($menuName, $key, $this->getScope($context));
        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate, $context);
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param array               $context
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate, array $context)
    {
        $menu = $this->getMenu($menuUpdate->getMenu());
        $menuItem = null;
        if (!$menuUpdate->isCustom()) {
            $menuItem = MenuUpdateUtils::findMenuItem($menu, $menuUpdate->getKey());
        }

        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate, ['menu_item' => $menuItem]);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );

        if (is_array($response)) {
            $response['scope'] = $this->getScope($context);
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);
            $response['menuItem'] = $menuItem;
        }

        return $response;
    }

    /**
     * @param array $context
     * @return Scope
     */
    protected function getScope(array $context)
    {
        return $this->get('oro_scope.scope_manager')->findOrCreate(
            $this->getScopeType(),
            $context
        );
    }

    /**
     * @param $menuName
     * @return ItemInterface
     */
    protected function getMenu($menuName)
    {
        $options = [
            MenuUpdateBuilder::SCOPE_CONTEXT_OPTION => $this->getContextForMenuUpdateBuilder()
        ];
        $menu = $this->getManager()->getMenu($menuName, $options);
        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    /**
     * @param $menu
     * @return array
     */
    protected function createMenuTree($menu)
    {
        return $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($menu);
    }
}
