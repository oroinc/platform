<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

/**
 * @RouteResource("menuupdates")
 * @NamePrefix("oro_api_")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Delete("/menuupdate/{menuName}/{key}")
     *
     * @param string $menuName
     * @param string $key
     *
     * @ApiDoc(
     *  description="Delete menu item for user"
     * )
     * @return Response
     */
    public function deleteAction($menuName, $key)
    {
        $em = $this->getEntityManager();
        $manager = $this->getManager();

        $menuUpdate = $manager->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            MenuUpdate::OWNERSHIP_USER,
            $this->getUser()->getId()
        );
        if ($menuUpdate === null) {
            throw $this->createNotFoundException();
        }

        $itemFromMenu = $manager->getMenuUpdateFromMenu(
            $menuName,
            $key,
            MenuUpdate::OWNERSHIP_USER,
            $this->getUser()->getId()
        );
        if ($itemFromMenu === null && $menuUpdate->getId() !== null) {
            $em->remove($menuUpdate);

            return new JsonResponse(null, 204);
        }

        $menuUpdate->setActive(false);

        if ($menuUpdate->getId() === null) {
            $em->persist($menuUpdate);
        }

        $em->flush();

        return new JsonResponse(null, 204);
    }

    /**
     * @return MenuUpdateManager
     */
    private function getManager()
    {
        return $this->get('oro_navigation.manager.menu_update_default');
    }

    /**
     * @return ObjectManager
     */
    private function getEntityManager()
    {
        return $this->getDoctrine()->getManagerForClass('Oro\Bundle\NavigationBundle\Entity\MenuUpdate');
    }
}
