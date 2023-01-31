<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateDisplayManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateMoveManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Helper\ContextRequestHelper;
use Oro\Bundle\ScopeBundle\Manager\ContextNormalizer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ajax Abstract Menu Controller
 */
abstract class AbstractAjaxMenuController extends AbstractController
{
    /**
     * @param string  $menuName
     * @param Request $request
     *
     * @return Response
     */
    public function resetAction($menuName, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();
        $scope = $this->getScopeManager()->find($manager->getScopeType(), $context);
        if (null === $scope) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $menuUpdateRepository = $manager->getRepository();

        // Disables query result cache to ensure we get all menu updates that should be removed.
        $menuUpdateRepository->setQueryResultCache(new NullAdapter());

        $updates = $menuUpdateRepository->findMenuUpdatesByScope($menuName, $scope);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());

        foreach ($updates as $update) {
            $em->remove($update);
        }

        $em->flush($updates);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param string  $menuName
     * @param string  $parentKey
     *
     * @return JsonResponse
     */
    public function createAction(Request $request, $menuName, $parentKey)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();
        $menu = $this->getMenu($menuName, $context);
        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $menuUpdate = $manager->createMenuUpdate(
            $menu,
            $scope,
            [
                'parentKey' => $parentKey,
                'divider' => $request->get('isDivider'),
            ]
        );
        $errors = $this->getValidator()->validate($menuUpdate);
        if ($errors->count()) {
            return new JsonResponse([
                'message' => $this->getValidationErrorMessage($errors)
            ], Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());
        $em->persist($menuUpdate);
        $em->flush();

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();

        $scope = $this->getScopeManager()->find($manager->getScopeType(), $context);

        if (!$scope) {
            throw $this->createNotFoundException();
        }

        $menuUpdate = $manager->findMenuUpdate($menuName, $key, $scope);
        if ($menuUpdate === null || $menuUpdate->getId() === null) {
            throw $this->createNotFoundException();
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());

        if ($menuUpdate->isCustom()) {
            $entityManager->remove($menuUpdate);
        } else {
            $menuUpdate->setActive(false);
            $entityManager->persist($menuUpdate);
        }

        $entityManager->flush($menuUpdate);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function showAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);

        $scope = $this->findOrCreateScope($context, $this->getMenuUpdateManager()->getScopeType());
        $menu = $this->getMenu($menuName, $context);
        $this->getMenuUpdateDisplayManager()->showMenuItem($menu, $key, $scope);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function hideAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);

        $scope = $this->findOrCreateScope($context, $this->getMenuUpdateManager()->getScopeType());
        $menu = $this->getMenu($menuName, $context);
        $this->getMenuUpdateDisplayManager()->hideMenuItem($menu, $key, $scope);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param string  $menuName
     *
     * @return JsonResponse
     */
    public function moveAction(Request $request, $menuName)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();

        $key = $request->get('key');
        $parentKey = $request->get('parentKey');
        $position = $request->get('position');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());

        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $menu = $this->getMenu($menuName, $context);
        $updates = $this->getMenuUpdateMoveManager()->moveMenuItem(
            $menu,
            $key,
            $scope,
            $parentKey,
            $position
        );
        foreach ($updates as $update) {
            $errors = $this->getValidator()->validate($update, null, ['Move']);

            if (count($errors)) {
                return new JsonResponse([
                    'message' => $this->getValidationErrorMessage($errors)
                ], Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($update);
        }

        $entityManager->flush();

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(['status' => true], Response::HTTP_OK);
    }

    /**
     * @param string $menuName
     * @param array  $context
     */
    protected function dispatchMenuUpdateScopeChangeEvent($menuName, array $context)
    {
        $this->get(EventDispatcherInterface::class)->dispatch(
            new MenuUpdateChangeEvent($menuName, $context),
            MenuUpdateChangeEvent::NAME
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getContextFromRequest(Request $request)
    {
        $manager = $this->getMenuUpdateManager();

        $context = $this->get(ContextRequestHelper::class)->getFromRequest(
            $request,
            $this->getAllowedContextKeys()
        );

        return $this->getScopeNormalizer()->denormalizeContext($manager->getScopeType(), $context);
    }

    /**
     * @param array  $context
     * @param string $scopeType
     * @return Scope
     */
    protected function findOrCreateScope($context, $scopeType)
    {
        $context = $this->getScopeNormalizer()->denormalizeContext($scopeType, $context);

        return $this->getScopeManager()->findOrCreate($scopeType, $context);
    }

    /**
     * @param string $menuName
     * @param array  $context
     *
     * @return ItemInterface
     */
    protected function getMenu($menuName, array $context)
    {
        $options = [
            MenuUpdateProvider::SCOPE_CONTEXT_OPTION => $context,
            BuilderChainProvider::IGNORE_CACHE_OPTION => true
        ];
        $menu = $this->get(BuilderChainProvider::class)->get($menuName, $options);

        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    /**
     * @return array
     */
    protected function getAllowedContextKeys()
    {
        return [];
    }

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

    private function getScopeManager(): ScopeManager
    {
        return $this->get(ScopeManager::class);
    }

    private function getValidationErrorMessage(ConstraintViolationList $constraintViolationList): string
    {
        $returnMessages = [];
        foreach ($constraintViolationList as $violation) {
            $returnMessages[] = $violation->getMessage();
        }

        return implode("\n", $returnMessages);
    }

    private function getScopeNormalizer(): ContextNormalizer
    {
        return $this->get(ContextNormalizer::class);
    }

    private function getValidator(): ValidatorInterface
    {
        return $this->get(ValidatorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventDispatcherInterface::class,
            ContextRequestHelper::class,
            BuilderChainProvider::class,
            MenuUpdateManager::class,
            MenuUpdateMoveManager::class,
            MenuUpdateDisplayManager::class,
            ScopeManager::class,
            TranslatorInterface::class,
            ContextNormalizer::class,
            ValidatorInterface::class,
        ]);
    }
}
