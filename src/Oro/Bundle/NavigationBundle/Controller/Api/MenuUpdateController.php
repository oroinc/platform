<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Knp\Menu\ItemInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

/**
 * @RouteResource("menuupdates")
 * @NamePrefix("oro_api_")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Delete("/menu/{ownershipType}/{menuName}/{key}")
     *
     * @ApiDoc(
     *  description="Delete menu item in specified scope."
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

        if (!$menuUpdate->isExistsInNavigationYml()) {
            $manager->removeMenuUpdate($menuUpdate);
        } else {
            $menuUpdate->setActive(false);
            $manager->updateMenuUpdate($menuUpdate);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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

        foreach ($updates as $update) {
            $manager->removeMenuUpdate($update);
        }

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

        $manager->updateMenuUpdate($currentUpdate);
        $manager->reorderMenuUpdate($menuName, $order, $ownershipType, $ownerId);

        return new JsonResponse(['status' => true], 200);
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
