<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\ORM\EntityManager;

use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Knp\Menu\ItemInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

/**
 * @RouteResource("menuupdates")
 * @NamePrefix("oro_api_")
 */
class MenuController extends Controller
{
    /**
     * @Delete("/menu/{ownershipType}/{menuName}/{key}")
     *
     * @ApiDoc(
     *  description="Delete or hide menu item."
     * )
     *
     * @param string $ownershipType
     * @param string $menuName
     * @param string $key
     *
     * @return Response
     */
    public function deleteAction($ownershipType, $menuName, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $menuUpdate = $manager->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType)
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
     * @Put("/menu/show/{ownershipType}/{menuName}/{key}")
     *
     * @ApiDoc(
     *  description="Make menu item visible."
     * )
     *
     * @param string $ownershipType
     * @param string $menuName
     * @param string $key
     *
     * @return Response
     */
    public function showAction($ownershipType, $menuName, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->showMenuItem($menuName, $key, $ownershipType, $this->getCurrentOwnerId($ownershipType));

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Put("/menu/hide/{ownershipType}/{menuName}/{key}")
     *
     * @ApiDoc(
     *  description="Make menu item hidden."
     * )
     *
     * @param string $ownershipType
     * @param string $menuName
     * @param string $key
     *
     * @return Response
     */
    public function hideAction($ownershipType, $menuName, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        $manager->hideMenuItem($menuName, $key, $ownershipType, $this->getCurrentOwnerId($ownershipType));

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Delete("/menu/reset/{ownershipType}/{menuName}")
     *
     * @ApiDoc(description="Reset menu to default state.")
     *
     * @param int    $ownershipType
     * @param string $menuName
     *
     * @return Response
     */
    public function resetAction($ownershipType, $menuName)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $updates = $manager->getMenuUpdatesByMenuAndScope(
            $menuName,
            $ownershipType,
            $this->getCurrentOwnerId($ownershipType)
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
     * @Put("/menu/move/{ownershipType}/{menuName}")
     *
     * @ApiDoc(description="Move menu item.")
     *
     * @param Request $request
     * @param int     $ownershipType
     * @param string  $menuName
     *
     * @return Response
     */
    public function moveAction(Request $request, $ownershipType, $menuName)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $ownerId = $this->getCurrentOwnerId($ownershipType);

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
     * @return int
     */
    private function getCurrentOwnerId($ownershipType)
    {
        $area = ConfigurationBuilder::DEFAULT_AREA;
        $provider = $this->get('oro_navigation.menu_update.builder')->getProvider($area, $ownershipType);

        return $provider->getId();
    }
}
