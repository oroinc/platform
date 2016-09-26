<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

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
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

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

        if (!$menuUpdate->isExistsInNavigationYml()) {
            $manager->removeMenuUpdate($menuUpdate);
        } else {
            $menuUpdate->setActive(false);
            $manager->updateMenuUpdate($menuUpdate);
        }

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
