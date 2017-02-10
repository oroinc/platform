<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

abstract class AbstractMenuController extends Controller
{
    /**
     * @param array $context
     */
    protected function checkAcl(array $context)
    {
        if (!$this->get('oro_security.security_facade')->isGranted('oro_navigation_manage_menus')) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @return MenuUpdateManager
     */
    protected function getMenuUpdateManager()
    {
        return $this->get('oro_navigation.manager.menu_update');
    }

    /**
     * @return String
     */
    protected function getScopeType()
    {
        return $this->getMenuUpdateManager()->getScopeType();
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return $this->getMenuUpdateManager()->getEntityClass();
    }

    /**
     * @param array $context
     * @return array
     */
    protected function index(array $context = [])
    {
        $this->checkAcl($context);

        return array_merge(
            [
                'entityClass' => $this->getEntityClass(),
                'context' => $context
            ],
            $this->denormalizeContext($context)
        );
    }

    /**
     * @param string $menuName
     * @param array  $context
     * @return array
     */
    protected function view($menuName, array $context = [])
    {
        $this->checkAcl($context);
        $denormalizedContext = $this->denormalizeContext($context);
        $menu = $this->getMenu($menuName, $denormalizedContext);

        return array_merge(
            [
                'entity' => $menu,
                'context' => $context,
                'tree' => $this->createMenuTree($menu)
            ],
            $denormalizedContext
        );
    }

    /**
     * @param string $menuName
     * @param string $parentKey
     * @param array  $context
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, array $context = [])
    {
        $this->checkAcl($context);
        $context = $this->denormalizeContext($context);
        $scope = $this->get('oro_scope.scope_manager')->findOrCreate($this->getScopeType(), $context, false);
        $menu = $this->getMenu($menuName, $context);
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate($menu,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'custom' => true,
                'scope' => $scope
            ]
        );

        return $this->handleUpdate($menuUpdate, $context, $menu);
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param array  $context
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, array $context = [])
    {
        $this->checkAcl($context);
        $context = $this->denormalizeContext($context);
        $scope = $this->get('oro_scope.scope_manager')->findOrCreate($this->getScopeType(), $context, false);
        $menu = $this->getMenu($menuName, $context);
        $menuUpdate = $this->getMenuUpdateManager()->findOrCreateMenuUpdate($menu, $key, $scope);

        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate, $context, $menu);
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param array               $context
     * @param ItemInterface       $menu
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate, array $context, ItemInterface $menu)
    {
        $menuItem = null;
        if (!$menuUpdate->isCustom()) {
            $menuItem = MenuUpdateUtils::findMenuItem($menu, $menuUpdate->getKey());
        }

        $form = $this->createForm(MenuUpdateType::class, $menuUpdate, ['menu_item' => $menuItem]);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );

        if (is_array($response)) {
            $response['context'] = $this->normalizeContext($context);
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);
            $response['menuItem'] = $menuItem;
            $response = array_merge($response, $context);
        } else {
            $this->dispatchMenuUpdateChangeEvent($menu->getName(), $context);
        }

        return $response;

    }

    /**
     * @param array $context
     * @return string[]
     */
    protected function normalizeContext(array $context)
    {
        return $this->get('oro_scope.context_normalizer')->normalizeContext($context);
    }

    /**
     * @param array $context
     * @return object[]
     */
    protected function denormalizeContext(array $context)
    {
        return $this->get('oro_scope.context_normalizer')->denormalizeContext($this->getScopeType(), $context);
    }

    /**
     * @param string $menuName
     * @param array  $context
     * @return ItemInterface
     */
    protected function getMenu($menuName, array $context)
    {
        $options = [
            MenuUpdateBuilder::SCOPE_CONTEXT_OPTION => $context,
            BuilderChainProvider::IGNORE_CACHE_OPTION => true
        ];
        $menu = $this->get('oro_menu.builder_chain')->get($menuName, $options);
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
     * @param string $menuName
     * @param array  $context
     */
    protected function dispatchMenuUpdateChangeEvent($menuName, array $context)
    {
        $this->get('event_dispatcher')->dispatch(
            MenuUpdateChangeEvent::NAME,
            new MenuUpdateChangeEvent($menuName, $context)
        );
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

    /**
     * @param Request $request
     * @param array   $allowedKeys
     * @return array
     */
    protected function getContextFromRequest(Request $request, array $allowedKeys = [])
    {
        return $this->get('oro_scope.context_request_helper')->getFromRequest($request, $allowedKeys);
    }
}
