<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AjaxMenuController extends Controller
{
    /**
     * @param string  $menuName
     * @param Request $request
     *
     * @return Response
     */
    public function resetAction($menuName, Request $request)
    {
        $this->checkAcl($request);
        $manager = $this->getMenuUpdateManager($request);
        $context = $this->getContextFromRequest($request);
        $scope = $this->get('oro_scope.scope_manager')->find($context);
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

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $scope);

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
        $this->checkAcl($request);
        $manager = $this->getMenuUpdateManager($request);

        $scope = $this->findOrCreateScope($request, $menuName);
        $menuUpdate = $manager->createMenuUpdate(
            $scope,
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

        $em = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());
        $em->persist($menuUpdate);
        $em->flush();

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $scope);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($menuName, $key, Request $request)
    {
        $this->checkAcl($request);
        $manager = $this->getMenuUpdateManager($request);

        $context = $this->getContextFromRequest($request);

        $scope = $this->get('oro_scope.scope_manager')->find($context);

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

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $scope);

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
        $this->checkAcl($request);
        $scope = $this->findOrCreateScope($request, $menuName);
        $this->getMenuUpdateManager($request)->showMenuItem($menuName, $key, $scope);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $scope);

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
        $this->checkAcl($request);
        $manager = $this->getMenuUpdateManager($request);

        $context = $this->getContextFromRequest($request);
        $scope = $this->findOrCreateScope($context, $manager->getScopeType());

        $manager->hideMenuItem($menuName, $key, $scope);

        $this->dispatchMenuUpdateScopeChangeEvent($menuName, $context);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param string  $menuName
     *
     * @return Response
     */
    public function moveAction(Request $request, $menuName)
    {
        $this->checkAcl($request);
        $manager = $this->getMenuUpdateManager($request);

        $key = $request->get('key');
        $parentKey = $request->get('parentKey');
        $position = $request->get('position');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass($manager->getEntityClass());

        $context = $this->getContextFromRequest($request);
        $scope = $this->findOrCreateScope($context, $manager->getScopeType());
        $updates = $manager->moveMenuItem(
            $menuName,
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
     * @param array $context
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
     * @param array   $allowedKeys
     * @return array
     */
    protected function getContextFromRequest(Request $request)
    {
        $allowedKeys = $request->attributes->get('_context_keys', []);

        return $this->get('oro_scope.context_request_helper')->getFromRequest($request, $allowedKeys);
    }

    /**
     * @param Request $request
     * @param string  $menuName
     * @return Scope
     */
    protected function findOrCreateScope($context, $scopeType)
    {
        return $this->get('oro_scope.scope_manager')->findOrCreate($scopeType, $context);
    }

    /**
     * @throws AccessDeniedException
     */
    protected function checkAcl(Request $request)
    {
        $acl = $request->attributes->get('_is_granted');
        $securityFacade = $this->get('oro_security.security_facade');
        if (!$securityFacade->isGranted('oro_navigation_manage_menus') || !$securityFacade->isGranted($acl)) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @return MenuUpdateManager
     */
    protected function getMenuUpdateManager(Request $request)
    {
        $managerName = $request->attributes->get('_manager');

        return $this->get($managerName);
    }
}
