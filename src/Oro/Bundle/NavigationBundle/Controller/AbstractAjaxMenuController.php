<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAjaxMenuController extends Controller
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
        $scope = $this->get('oro_scope.scope_manager')->find($manager->getScopeType(), $context);
        if (null === $scope) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $updates = $manager->getRepository()->findMenuUpdatesByScope($menuName, $scope);

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
     * @return Response
     */
    public function createAction(Request $request, $menuName, $parentKey)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();
        $menu = $this->getMenu($menuName, $context);
        $menuUpdate = $manager->createMenuUpdate(
            $menu,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'isDivider' => $request->get('isDivider'),
                'custom' => true
            ]
        );
        $errors = $this->get('validator')->validate($menuUpdate);
        if (count($errors)) {
            $message = $this->get('translator')->trans('oro.navigation.menuupdate.validation_error_message');

            return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
        }
        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $menuUpdate->setScope($scope);

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
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();

        $scope = $this->get('oro_scope.scope_manager')->find($manager->getScopeType(), $context);

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
     * @return Response
     */
    public function showAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();

        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $menu = $this->getMenu($menuName, $context);
        $this->getMenuUpdateManager()->showMenuItem($menu, $key, $scope);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function hideAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $this->checkAcl($context);
        $manager = $this->getMenuUpdateManager();

        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $menu = $this->getMenu($menuName, $context);
        $manager->hideMenuItem($menu, $key, $scope);

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
        $updates = $manager->moveMenuItem(
            $menu,
            $key,
            $scope,
            $parentKey,
            $position
        );
        foreach ($updates as $update) {
            $errors = $this->get('validator')->validate($update);

            if (count($errors)) {
                $message = $this->get('translator')->trans('oro.navigation.menuupdate.validation_error_message');

                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
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
        $this->get('event_dispatcher')->dispatch(
            MenuUpdateChangeEvent::NAME,
            new MenuUpdateChangeEvent($menuName, $context)
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getContextFromRequest(Request $request)
    {
        $manager = $this->getMenuUpdateManager();

        $context = $this->get('oro_scope.context_request_helper')->getFromRequest(
            $request,
            $this->getAllowedContextKeys()
        );

        return $this->get('oro_scope.context_normalizer')->denormalizeContext($manager->getScopeType(), $context);
    }

    /**
     * @param array  $context
     * @param string $scopeType
     * @return Scope
     */
    protected function findOrCreateScope($context, $scopeType)
    {
        $context = $this->get('oro_scope.context_normalizer')->denormalizeContext($scopeType, $context);

        return $this->get('oro_scope.scope_manager')->findOrCreate($scopeType, $context);
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
        $menu = $this->get('oro_menu.builder_chain')->get($menuName, $options);

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
     * @param array $context
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted('oro_navigation_manage_menus')) {
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
}
