<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateWithScopeChangeEvent;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateDisplayManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateMoveManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Helper\ContextRequestHelper;
use Oro\Bundle\ScopeBundle\Manager\ContextNormalizer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base class for menu related controllers.
 */
abstract class AbstractMenuController extends AbstractController
{
    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted('oro_navigation_manage_menus')) {
            throw $this->createAccessDeniedException();
        }
    }

    protected function getMenuUpdateManager(): MenuUpdateManager
    {
        return $this->container->get(MenuUpdateManager::class);
    }

    protected function getMenuUpdateMoveManager(): MenuUpdateMoveManager
    {
        return $this->container->get(MenuUpdateMoveManager::class);
    }

    protected function getMenuUpdateDisplayManager(): MenuUpdateDisplayManager
    {
        return $this->container->get(MenuUpdateDisplayManager::class);
    }

    protected function getScopeType(): string
    {
        return $this->getMenuUpdateManager()->getScopeType();
    }

    protected function getEntityClass(): string
    {
        return $this->getMenuUpdateManager()->getEntityClass();
    }

    protected function index(array $context = []): array
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

    protected function view(string $menuName, array $context = []): array
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

    protected function create(string $menuName, ?string $parentKey, array $context = []): array|RedirectResponse
    {
        $this->checkAcl($context);
        $context = $this->denormalizeContext($context);
        $scope = $this->get(ScopeManager::class)->findOrCreate($this->getScopeType(), $context, false);
        $menu = $this->getMenu($menuName, $context);
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
            $menu,
            $scope,
            [
                'parentKey' => $parentKey,
            ]
        );

        return $this->handleUpdate($menuUpdate, $context, $menu);
    }

    protected function update(string $menuName, ?string $key, array $context = []): array|RedirectResponse
    {
        $this->checkAcl($context);
        $context = $this->denormalizeContext($context);
        $scope = $this->get(ScopeManager::class)->findOrCreate($this->getScopeType(), $context, false);
        $menu = $this->getMenu($menuName, $context);

        if ($key === null) {
            $key = $menuName;
        }

        $menuUpdate = $this->getMenuUpdateManager()->findOrCreateMenuUpdate($menu, $scope, ['key' => $key]);

        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate, $context, $menu);
    }

    protected function move(Request $request, string $menuName, array $context = []): Response|RedirectResponse
    {
        $this->checkAcl($context);
        $context = $this->denormalizeContext($context);

        $menu = $this->getMenu($menuName, $context);

        $handler = $this->get(MenuUpdateTreeHandler::class);
        $treeItems = $handler->getTreeItemList($menu, true);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($treeItems, array_flip($request->get('selected', [])));

        $treeData = $handler->createTree($menu, true);
        $handler->disableTreeItems($collection->source, $treeData);
        $form = $this->createForm(TreeMoveType::class, $collection, [
            'tree_items' => $treeItems,
            'tree_data' => $treeData,
        ]);

        $responseData = [
            'context' => $context,
            'menuName' => $menu->getName(),
            'treeItems' => $treeItems,
            'changed' => [],
        ];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getDoctrine()->getManagerForClass($this->getEntityClass());
            $scope = $this->get(ScopeManager::class)->findOrCreate($this->getScopeType(), $context);
            $updates = $this->getMenuUpdateMoveManager()->moveMenuItems(
                $menu,
                $collection->source,
                $scope,
                $collection->target->getKey(),
                count($collection->target->getChildren())
            );

            foreach ($updates as $update) {
                $errors = $this->get(ValidatorInterface::class)->validate($update, null, ['Move']);
                if (count($errors)) {
                    $form->addError(new FormError(
                        $this->get(TranslatorInterface::class)
                            ->trans('oro.navigation.menuupdate.validation_error_message')
                    ));
                    return $this->renderMoveDialog($responseData, $form);
                }
                $entityManager->persist($update);
                $responseData['changed'][] = [
                    'id' => $update->getKey(),
                    'parent' => $collection->target->getKey(),
                    'position' => $update->getPriority()
                ];
            }

            $entityManager->flush();
            $this->dispatchMenuUpdateChangeEvent($menuName, $context);

            $responseData['saved'] = true;
        }

        return $this->renderMoveDialog($responseData, $form);
    }

    protected function renderMoveDialog(array $params, FormInterface $form): Response
    {
        $params = array_merge($params, ['form' => $form->createView()]);

        return $this->render('@OroNavigation/menuUpdate/dialog/move.html.twig', $params);
    }

    protected function handleUpdate(
        MenuUpdateInterface $menuUpdate,
        array $context,
        ItemInterface $menu
    ): array|RedirectResponse {
        $menuItem = MenuUpdateUtils::findMenuItem($menu, $menuUpdate->getKey());

        $form = $this->createForm(MenuUpdateType::class, $menuUpdate, ['menu_item' => $menuItem, 'menu' => $menu]);

        $response = $this->get(UpdateHandlerFacade::class)->update(
            $menuUpdate,
            $form,
            $this->getSavedSuccessMessage()
        );

        if (\is_array($response)) {
            $response['context'] = $this->normalizeContext($context);
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);
            $response['menuItem'] = $menuItem;
            $response['menu'] = $menu;
            $response = array_merge($response, $context);
        } else {
            $this->dispatchMenuUpdateChangeEvent($menu->getName(), $context);
        }

        return $response;
    }

    /**
     * @return string[]
     */
    protected function normalizeContext(array $context): array
    {
        return $this->get(ContextNormalizer::class)->normalizeContext($context);
    }

    /**
     * @return object[]
     */
    protected function denormalizeContext(array $context): array
    {
        return $this->get(ContextNormalizer::class)->denormalizeContext($this->getScopeType(), $context);
    }

    protected function getMenu(string $menuName, array $context): ItemInterface
    {
        $options = [
            MenuUpdateProvider::SCOPE_CONTEXT_OPTION => $context,
            BuilderChainProvider::IGNORE_CACHE_OPTION => true,
            BuilderChainProvider::MENU_LOCAL_CACHE_PREFIX => 'edit_'
        ];

        $configurationRootMenuKeys = array_keys($this->get(ConfigurationProvider::class)->getMenuTree());
        $isMenuFromConfiguration = in_array($menuName, $configurationRootMenuKeys, true);

        $menu = $this->get(BuilderChainProvider::class)->get($menuName, $options);

        if (!$isMenuFromConfiguration && !count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    protected function createMenuTree($menu): array
    {
        return $this->get(MenuUpdateTreeHandler::class)->createTree($menu);
    }

    protected function dispatchMenuUpdateChangeEvent(string $menuName, array $context): void
    {
        $this->get(EventDispatcherInterface::class)->dispatch(
            new MenuUpdateChangeEvent($menuName, $context),
            MenuUpdateChangeEvent::NAME
        );
    }

    protected function getCurrentOrganization():? Organization
    {
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        return $token instanceof OrganizationAwareTokenInterface
            ? $token->getOrganization()
            : null;
    }

    protected function getContextFromRequest(Request $request, array $allowedKeys = []): array
    {
        return $this->get(ContextRequestHelper::class)->getFromRequest($request, $allowedKeys);
    }

    protected function getSavedSuccessMessage(): string
    {
        return $this->renderView('@OroNavigation/menuUpdate/savedSuccessMessage.html.twig');
    }

    protected function updateDependentMenuUpdateUrls(MenuUpdateInterface $menuUpdate): void
    {
        $repo = $this->getMenuUpdateManager()->getRepository();
        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        $repo->updateDependentMenuUpdates($menuUpdate);

        foreach ($repo->getDependentMenuUpdateScopes($menuUpdate) as $scope) {
            $eventDispatcher->dispatch(
                new MenuUpdateWithScopeChangeEvent($menuUpdate->getMenu(), $scope),
                MenuUpdateWithScopeChangeEvent::NAME
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ScopeManager::class,
                MenuUpdateTreeHandler::class,
                ValidatorInterface::class,
                TranslatorInterface::class,
                ContextNormalizer::class,
                ConfigurationProvider::class,
                BuilderChainProvider::class,
                EventDispatcherInterface::class,
                ContextRequestHelper::class,
                MenuUpdateManager::class,
                MenuUpdateMoveManager::class,
                MenuUpdateDisplayManager::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
