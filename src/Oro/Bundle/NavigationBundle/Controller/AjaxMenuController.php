<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Doctrine\ORM\EntityManager;

use Knp\Menu\ItemInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

class AjaxMenuController extends Controller
{
    /**
     * @Route("/menu/reset/{ownershipType}/{menuName}", name="oro_navigation_menuupdate_reset")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $ownershipType
     *
     * @return Response
     */
    public function resetAction(Request $request, $menuName, $ownershipType)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $updates = $manager->getMenuUpdatesByMenuAndScope(
            $menuName,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType, $request->get('ownerId'))
        );

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        foreach ($updates as $update) {
            $em->remove($update);
        }

        $em->flush($updates);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/menu/create/{menuName}/{parentKey}/{ownershipType}", name="oro_navigation_menuupdate_create")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $parentKey
     * @param string  $ownershipType
     *
     * @return Response
     */
    public function createAction(Request $request, $menuName, $parentKey, $ownershipType)
    {
        $menuUpdate = $this->get('oro_navigation.manager.menu_update_default')->createMenuUpdate(
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType, $request->get('ownerId')),
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'isDivider'=> $request->get('isDivider'),
                'custom' => true
            ]
        );
        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);
        $em->persist($menuUpdate);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/menu/delete/{ownershipType}/{menuName}/{key}", name="oro_navigation_menuupdate_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $key
     * @param string  $ownershipType
     *
     * @return Response
     */
    public function deleteAction(Request $request, $menuName, $key, $ownershipType)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $menuUpdate = $manager->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType, $request->get('ownerId'))
        );
        if ($menuUpdate === null) {
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
     * @Route("/menu/show/{ownershipType}/{menuName}/{key}", name="oro_navigation_menuupdate_show")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $key
     * @param string  $ownershipType
     *
     * @return Response
     */
    public function showAction(Request $request, $menuName, $key, $ownershipType)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->showMenuItem(
            $menuName,
            $key,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType, $request->get('ownerId'))
        );

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/hide/{ownershipType}/{menuName}/{key}", name="oro_navigation_menuupdate_hide")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $key
     * @param string  $ownershipType
     *
     * @return Response
     */
    public function hideAction(Request $request, $menuName, $key, $ownershipType)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->hideMenuItem(
            $menuName,
            $key,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType, $request->get('ownerId'))
        );

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/menu/move/{ownershipType}/{menuName}", name="oro_navigation_menuupdate_move")
     * @Method("PUT")
     *
     * @param Request $request
     * @param int     $ownershipType
     * @param string  $menuName
     *
     * @return Response
     */
    public function moveAction(Request $request, $menuName, $ownershipType)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $ownerId = $this->getCurrentOwnerId($ownershipType, $request->get('ownerId'));

        $key = $request->get('key');
        $currentUpdate = $manager->getMenuUpdateByKeyAndScope($menuName, $key, $ownershipType, $ownerId);

        $parentKey = $request->get('parentKey');
        $parent = $manager->findMenuItem($menuName, $parentKey, $ownershipType);
        $currentUpdate->setParentKey($parent ? $parent->getName() : null);

        $i = 0;
        $order = [];
        $parent = !$parent ? $manager->getMenu($menuName) : $parent;

        $position = $request->get('position');
        /** @var ItemInterface $child */
        foreach ($parent->getChildren() as $child) {
            if ($position == $i++) {
                $currentUpdate->setPriority($i++);
            }

            if ($child->getName() != $key) {
                $order[$i] = $child;
            }
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass(MenuUpdate::class);

        $updates = array_merge(
            [$currentUpdate],
            $manager->getReorderedMenuUpdates($menuName, $order, $ownershipType, $ownerId)
        );

        $errors = [];
        foreach ($updates as $update) {
            $errors = $this->get('validator')->validate($update, 'move');
            if (count($errors)) {
                break;
            }

            $em->persist($update);
        }

        if (!count($errors)) {
            $em->flush($updates);
            return new JsonResponse(['status' => true], Response::HTTP_OK);
        }

        return new JsonResponse(['status' => false, 'message' => (string) $errors], Response::HTTP_OK);
    }

    /**
     * @param string $ownershipType
     * @param null|int $ownerId
     *
     * @return int
     */
    private function getCurrentOwnerId($ownershipType, $ownerId = null)
    {
        if ($ownerId) {
            return $ownerId;
        }
        $area = ConfigurationBuilder::DEFAULT_AREA;
        $provider = $this->get('oro_navigation.menu_update.builder')->getProvider($area, $ownershipType);

        return $provider->getId();
    }
}
