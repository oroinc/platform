<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
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
     * @Acl(
     *      id="oro_dashboard_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroDashboardBundle:Dashboard"
     * )
     * @return Response
     */
    public function deleteAction(Dashboard $id)
    {
        $dashboard = $id;
        $this->getEntityManager()->remove($dashboard);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Response::HTTP_NO_CONTENT));
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }
}
