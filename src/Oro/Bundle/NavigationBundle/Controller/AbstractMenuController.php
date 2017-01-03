<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param array $context
     * @return array
     */
    protected function index(array $context = [])
    {
        $this->checkAcl();

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
        $this->checkAcl();
        $menu = $this->getMenu($menuName, $context);

        return array_merge(
            [
                'entity' => $menu,
                'context' => $context,
                'tree' => $this->createMenuTree($menu)
            ],
            $this->denormalizeContext($context)
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
        $this->checkAcl();
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
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
     * @param string $menuName
     * @param string $key
     * @param array  $context
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, array $context = [])
    {
        $this->checkAcl();

        $scope = $this->get('oro_scope.scope_manager')->find($this->getScopeType(), $context);
        $menuUpdate = null;
        if ($scope) {
            $menuUpdate = $this->getMenuUpdateManager()->findMenuUpdate($menuName, $key, $scope);
        }
        if (null === $menuUpdate) {
            $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
                $context,
                [
                    'menu' => $menuName,
                    'key' => $key,
                    'custom' => true
                ]
            );
        }

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
        $menu = $this->getMenu($menuUpdate->getMenu(), $context);
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
            $response['context'] = $context;
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);
            $response['menuItem'] = $menuItem;
        }

        return array_merge($response, $this->denormalizeContext($context));
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
            MenuUpdateBuilder::SCOPE_CONTEXT_OPTION => $context
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

    /**
     * @param Request $request
     * @return array
     */
    protected function getContextFromRequest(Request $request)
    {
        $context = (array)$request->query->get('context', []);
        if (empty($context)) {
            throw $this->createNotFoundException('Context can\'t be empty');
        }

        return $context;
    }
}
