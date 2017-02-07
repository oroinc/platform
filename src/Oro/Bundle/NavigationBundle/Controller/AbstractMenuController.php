<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;

use Knp\Menu\ItemInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateScopeChangeEvent;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;

abstract class AbstractMenuController extends Controller
{
    /**
     * @return String
     */
    abstract protected function getScopeType();

    /**
     * @return MenuUpdateManager
     */
    abstract protected function getMenuUpdateManager();

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
     * @param array  $menuTreeContext
     * @return array
     */
    protected function view($menuName, array $context = [], array $menuTreeContext = [])
    {
        $this->checkAcl();

        $menu = $this->getMenu($menuName, $menuTreeContext);

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
     * @param array  $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, array $context = [], array $menuTreeContext = [])
    {
        $this->checkAcl();

        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
            $this->getScope($context),
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'custom' => true
            ]
        );

        return $this->handleUpdate($menuUpdate, $context, $menuTreeContext);
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param array  $context
     * @param array  $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, array $context = [], array $menuTreeContext = [])
    {
        $this->checkAcl();

        $menuUpdate = $this->getMenuUpdateManager()->findOrCreateMenuUpdate($menuName, $key, $this->getScope($context));
        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate, $context, $menuTreeContext);
    }

    /**
     * @param Request $request
     * @param string  $menuName
     * @param array   $context
     * @param array   $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function move(Request $request, $menuName, array $context = [], array $menuTreeContext = [])
    {
        $this->checkAcl();

        $menu = $this->getMenu($menuName, $menuTreeContext);
        $scope = $this->getScope($context);

        $handler = $this->get('oro_navigation.tree.menu_update_tree_handler');
        $choices = $handler->getTreeItemList($menu, true);
        $selected = $request->get('selected', []);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($choices, array_flip($selected));

        $form = $this->createForm(TreeMoveType::class, $collection, [
            'source_config' => [
                'choices' => $choices,
            ],
            'target_config' => [
                'choices' => $choices,
            ],
        ]);

        $responseData = [
            'scope' => $scope,
            'menuName' => $menu->getName()
        ];

        $manager = $this->get('oro_navigation.manager.menu_update');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

            $updates = $manager->moveMenuItems(
                $menuName,
                $collection->source,
                $scope,
                $collection->target->getKey(),
                0
            );

            $changed = [];

            foreach ($updates as $update) {
                $errors = $this->get('validator')->validate($update);
                if (count($errors)) {
                    $form->addError(new FormError(
                        $this->get('translator')->trans('oro.navigation.menuupdate.validation_error_message')
                    ));
                    $responseData['form'] = $form->createView();

                    return $responseData;
                }
                $entityManager->persist($update);
                $changed[] = [
                    'id' => $update->getKey(),
                    'parent' => $collection->target->getKey(),
                    'position' => $update->getPriority()
                ];
            }

            $entityManager->flush();
            $this->dispatchMenuUpdateScopeChangeEvent($menuName, $scope);

            $responseData['saved'] = true;
            $responseData['changed'] = $changed;
        }
        $responseData['form'] = $form->createView();

        return $responseData;
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param array               $context
     * @param array               $menuTreeContext
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate, array $context = [], array $menuTreeContext = [])
    {
        $menu = $this->getMenu($menuUpdate->getMenu(), $menuTreeContext);
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

        $scope = $this->getScope($context);

        if (is_array($response)) {
            $response['scope'] = $scope;
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);
            $response['menuItem'] = $menuItem;
        } else {
            $this->dispatchMenuUpdateScopeChangeEvent($menu->getName(), $scope);
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
     * @param string $menuName
     * @param array  $menuTreeContext
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
     * @param string $menuName
     * @param Scope $scope
     */
    protected function dispatchMenuUpdateScopeChangeEvent($menuName, Scope $scope)
    {
        $this->get('event_dispatcher')->dispatch(
            MenuUpdateScopeChangeEvent::NAME,
            new MenuUpdateScopeChangeEvent($menuName, $scope)
        );
    }
}
