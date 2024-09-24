<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for dashboards.
 */
class DashboardController extends AbstractFOSRestController
{
    /**
     * @param Dashboard $id
     *
     * @ApiDoc(
     *      description="Delete dashboard",
     *      resource=true
     * )
     * @return Response
     */
    #[Acl(id: 'oro_dashboard_delete', type: 'entity', class: Dashboard::class, permission: 'DELETE')]
    public function deleteAction(Dashboard $id)
    {
        $dashboard = $id;
        $em = $this->getEntityManager();
        $em->remove($dashboard);
        $em->flush();

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine')->getManager();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
