<?php

namespace Oro\Bundle\ReportBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Report entity.
 */
class ReportController extends RestController
{
    /**
     * Remove report.
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Report",
     *      resource=true
     * )
     * @return Response
     */
    #[Acl(id: 'oro_report_delete', type: 'entity', class: Report::class, permission: 'DELETE')]
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
        return $this->container->get('oro_report.report.manager.api');
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
