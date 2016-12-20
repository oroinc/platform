<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
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
    protected function getScopeType()
    {
        return $this->getParameter('oro_navigation.menu_update.scope_type');
    }

    /**
     * @return MenuUpdateManager
     */
    protected function getMenuUpdateManager()
    {
        return $this->get('oro_navigation.manager.menu_update');
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return MenuUpdate::class;
    }

    /**
     * @return mixed
     */
    protected function getFormTypeClass()
    {
        return MenuUpdateType::class;
    }

    /**
     * @throws AccessDeniedException
     */
    abstract protected function checkAcl();

    /**
     * @param Scope $scope
     * @return array
     */
    protected function index(Scope $scope)
    {
        $this->checkAcl();

        return [
            'entityClass' => $this->getEntityClass(),
            'scope' => $scope
        ];
    }

    /**
     * @param string $menuName
     * @param Scope  $scope
     * @param array  $menuTreeContext
     * @return array
     */
    protected function view($menuName, Scope $scope, array $menuTreeContext = [])
    {
        $this->checkAcl();

        $menu = $this->getMenu($menuName, $menuTreeContext);

        return [
            'entity' => $menu,
            'scope' => $scope,
            'tree' => $this->createMenuTree($menu)
        ];
    }

    /**
     * @param string $menuName
     * @param string $parentKey
     * @param Scope  $scope
     * @param array  $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, Scope $scope, array $menuTreeContext = [])
    {
        $this->checkAcl();

        /** @var MenuUpdateInterface $menuUpdate */
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
            $scope,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'custom' => true
            ]
        );

        return $this->handleUpdate($menuUpdate, $scope, $menuTreeContext);
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param Scope  $scope
     * @param array  $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, Scope $scope, array $menuTreeContext = [])
    {
        $this->checkAcl();

        $menuUpdate = $this->getMenuUpdateManager()->findOrCreateMenuUpdate($menuName, $key, $scope);
        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate, $scope, $menuTreeContext);
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param Scope               $scope
     * @param array               $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate, Scope $scope, array $menuTreeContext = [])
    {
        $menu = $this->getMenu($menuUpdate->getMenu(), $menuTreeContext);
        $menuItem = null;
        if (!$menuUpdate->isCustom()) {
            $menuItem = MenuUpdateUtils::findMenuItem($menu, $menuUpdate->getKey());
        }

        $form = $this->createForm($this->getFormTypeClass(), $menuUpdate, ['menu_item' => $menuItem]);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );

        if (is_array($response)) {
            $response['scope'] = $scope;
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
    protected function getScope(array $context = [])
    {
        return $this->get('oro_scope.scope_manager')->findOrCreate(
            $this->getScopeType(),
            $context
        );
    }

    /**
     * @param       $menuName
     * @param array $menuTreeContext
     * @return ItemInterface
     */
    protected function getMenu($menuName, array $menuTreeContext = [])
    {
        $options = [
            MenuUpdateBuilder::SCOPE_CONTEXT_OPTION => $menuTreeContext
        ];
        $menu = $this->getMenuUpdateManager()->getMenu($menuName, $options);
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

    /**
     * @return null|\Oro\Bundle\OrganizationBundle\Entity\Organization
     */
    protected function getCurrentOrganization()
    {
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        return $token instanceof OrganizationContextTokenInterface ? $token->getOrganizationContext() : null;
    }
}
