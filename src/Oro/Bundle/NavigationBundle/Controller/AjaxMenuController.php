<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

// todo check acl
class AjaxMenuController extends Controller
{
    /**
     * @Route("/menu/reset/{menuName}", name="oro_navigation_menuupdate_reset")
     * @Method("DELETE")
     *
     * @param string  $menuName
     * @param Request $request
     *
     * @return Response
     */
    public function resetAction($menuName, Request $request)
    {
        $context = $this->getContextFromRequest($request);
        $scope = $this->get('oro_scope.scope_manager')->find($context);
        if (null === $scope) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $updates = $this->getMenuUpdateManager()->getRepository()->findMenuUpdatesByScope($menuName, $scope);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        foreach ($updates as $update) {
            $em->remove($update);
        }

        $em->flush($updates);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/menu/create/{menuName}/{parentKey}", name="oro_navigation_menuupdate_create")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $parentKey
     *
     * @return Response
     */
    public function createAction(Request $request, $menuName, $parentKey)
    {
        $scope = $this->findOrCreateScope($request, $menuName);
        $menuUpdate = $this->getMenuUpdateManager()->createMenuUpdate(
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

        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);
        $em->persist($menuUpdate);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/menu/delete/{menuName}/{key}", name="oro_navigation_menuupdate_delete")
     * @Method("DELETE")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($menuName, $key, Request $request)
    {
        $context = $this->getContextFromRequest($request);

        $scope = $this->get('oro_scope.scope_manager')->find($context);

        if (!$scope) {
            throw $this->createNotFoundException();
        }

        $manager = $this->getMenuUpdateManager();

        $menuUpdate = $manager->findMenuUpdate($menuName, $key, $scope);
        if ($menuUpdate === null || $menuUpdate->getId() === null) {
            throw $this->createNotFoundException();
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        if ($menuUpdate->isCustom()) {
            $entityManager->remove($menuUpdate);
        } else {
            $menuUpdate->setActive(false);
            $entityManager->persist($menuUpdate);
        }

        $entityManager->flush($menuUpdate);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/menu/show/{menuName}/{key}", name="oro_navigation_menuupdate_show")
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function showAction($menuName, $key, Request $request)
    {
        $scope = $this->findOrCreateScope($request, $menuName);
        $this->getMenuUpdateManager()->showMenuItem($menuName, $key, $scope);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/hide/{menuName}/{key}", name="oro_navigation_menuupdate_hide")
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function hideAction($menuName, $key, Request $request)
    {
        $scope = $this->findOrCreateScope($request, $menuName);
        $this->getMenuUpdateManager()->hideMenuItem($menuName, $key, $scope);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/move/{menuName}", name="oro_navigation_menuupdate_move")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $menuName
     *
     * @return Response
     */
    public function moveAction(Request $request, $menuName)
    {
        $manager = $this->getMenuUpdateManager();

        $key = $request->get('key');
        $parentKey = $request->get('parentKey');
        $position = $request->get('position');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        $scope = $this->findOrCreateScope($request, $menuName);
        $updates = $manager->moveMenuItem($menuName, $key,
            $scope, $parentKey,
            $position);
        foreach ($updates as $update) {
            $errors = $this->get('validator')->validate($update);
            if (count($errors)) {
                $message = $this->get('translator')->trans('oro.navigation.menuupdate.validation_error_message');

                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($update);
        }

        $entityManager->flush();

        return new JsonResponse(['status' => true], Response::HTTP_OK);
    }

    /**
     * @return MenuUpdateManager
     */
    protected function getMenuUpdateManager()
    {
        // todo select right manager here
        return $this->get('oro_navigation.manager.menu_update');
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

    /**
     * @param Request $request
     * @param string  $menuName
     * @return \Extend\Entity\EX_OroScopeBundle_Scope|Scope
     */
    protected function findOrCreateScope(Request $request, $menuName)
    {
        $context = $this->getContextFromRequest($request);
        $menu = $this->getMenuUpdateManager()->getMenu($menuName);
        $scopeType = $menu->getExtra('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE);

        return $this->get('oro_scope.scope_manager')->findOrCreate($scopeType, $context);
    }
}
