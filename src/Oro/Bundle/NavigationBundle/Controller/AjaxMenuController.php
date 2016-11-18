<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class AjaxMenuController extends Controller
{
    /**
     * @Route("/menu/reset/{scopeId}/{menuName}", name="oro_navigation_menuupdate_reset")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("DELETE")
     *
     * @param string  $menuName
     * @param Scope   $scope
     *
     * @return Response
     */
    public function resetAction($menuName, Scope $scope)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $updates = $manager->getMenuUpdatesByMenuAndScope($menuName, $scope);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        foreach ($updates as $update) {
            $em->remove($update);
        }

        $em->flush($updates);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/menu/create/{menuName}/{parentKey}/{scopeId}", name="oro_navigation_menuupdate_create")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $parentKey
     * @param Scope   $scope
     *
     * @return Response
     */
    public function createAction(Request $request, $menuName, $parentKey, Scope $scope)
    {
        $menuUpdate = $this->get('oro_navigation.manager.menu_update_default')->createMenuUpdate(
            $scope,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'isDivider'=> $request->get('isDivider'),
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
     * @Route("/menu/delete/{scopeId}/{menuName}/{key}", name="oro_navigation_menuupdate_delete")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("DELETE")
     *
     * @param string $menuName
     * @param string $key
     * @param Scope  $scope
     *
     * @return Response
     */
    public function deleteAction($menuName, $key, Scope $scope)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $menuUpdate = $manager->getMenuUpdateByKeyAndScope($menuName, $key, $scope);
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
     * @Route("/menu/show/{scopeId}/{menuName}/{key}", name="oro_navigation_menuupdate_show")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Scope   $scope
     *
     * @return Response
     */
    public function showAction($menuName, $key, Scope $scope)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->showMenuItem($menuName, $key, $scope);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/hide/{scopeId}/{menuName}/{key}", name="oro_navigation_menuupdate_hide")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Scope   $scope
     *
     * @return Response
     */
    public function hideAction($menuName, $key, Scope $scope)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->hideMenuItem($menuName, $key, $scope);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/move/{scopeId}/{menuName}", name="oro_navigation_menuupdate_move")
     * @ParamConverter("scope", class="OroScopeBundle:Scope", options={"id" = "scopeId"})
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $menuName
     * @param Scope   $scope
     *
     * @return Response
     */
    public function moveAction(Request $request, $menuName, Scope $scope)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $key = $request->get('key');
        $parentKey = $request->get('parentKey');
        $position = $request->get('position');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        $updates = $manager->moveMenuItem($menuName, $key, $scope, $parentKey, $position);
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
}
