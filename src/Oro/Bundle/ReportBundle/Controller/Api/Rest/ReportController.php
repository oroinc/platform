<?php

namespace Oro\Bundle\ReportBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * API CRUD controller for Report entity.
 *
 * @RouteResource("report")
 * @NamePrefix("oro_api_")
 */
class ReportController extends RestController implements ClassResourceInterface
{
    /**
     * Remove report.
     *
     * @param int $id
     *
     * @Rest\Delete(requirements={"id"="\d+"})
     *
     * @ApiDoc(
     *      description="Delete Report",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_report_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroReportBundle:Report"
     * )
     * @return Response
     */
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_report.report.manager.api');
    }

    /**
     * @return FormInterface
     * @throws \RuntimeException
     */
    public function getForm()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }

    /**
     * @return ApiFormHandler
     * @throws \RuntimeException
     */
    public function getFormHandler()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }
}
