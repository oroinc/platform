<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

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
     *  description="Delete menu item for user"
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
        /** @var ObjectManager $em */
        $em = $this->getDoctrine()->getManagerForClass('Oro\Bundle\NavigationBundle\Entity\MenuUpdate');

        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        /** @var MenuUpdateHelper $helper */
        $helper = $this->get('oro_navigation.helper.menu_update');

        if ($ownershipType == MenuUpdate::OWNERSHIP_ORGANIZATION) {
            $ownerId = $this->getCurrentOrganization()->getId();
        } else {
            $ownerId = $this->getCurrentUser()->getId();
        }

        $menuUpdate = $manager->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $ownershipType,
            $ownerId
        );
        if ($menuUpdate === null) {
            throw $this->createNotFoundException();
        }

        $item = $helper->findMenuItem($manager->getMenu($menuName), $key);
        if ($item->getExtra('doesNotExistInNavigationYML')) {
            $em->remove($menuUpdate);
            $em->flush();

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
     * @return null|User
     */
    private function getCurrentUser()
    {
        $user = $this->get('oro_security.security_facade')->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @return null|Organization
     */
    private function getCurrentOrganization()
    {
        $organization = $this->get('oro_security.security_facade')->getOrganization();
        if (!is_bool($organization)) {
            return $organization;
        }

        return null;
    }
}
