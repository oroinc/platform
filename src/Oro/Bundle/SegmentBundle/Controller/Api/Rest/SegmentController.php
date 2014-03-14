<?php

namespace Oro\Bundle\SegmentBundle\Controller\Api\Rest;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;

/**
 * @RouteResource("segment")
 * @NamePrefix("oro_api_")
 */
class SegmentController extends RestController implements ClassResourceInterface
{
    /**
     * Get entity segments.
     *
     * @param string $entityName Entity full class name; backslashes (\) should be replaced with underscore (_).
     * @QueryParam(
     *      name="term", nullable=true, strict=false, default="",
     *      description="Search term")
     *
     * @Get(name="oro_api_get_segment_items", requirements={"entityName"="((\w+)_)+(\w+)"})
     * @ApiDoc(
     *      description="Get entity segments",
     *      resource=true
     * )
     * @return Response
     */
    public function getItemsAction($entityName)
    {
        $entityName = str_replace('_', '\\', $entityName);
        $term       = $this->getRequest()->query->get('term');
        $statusCode = Codes::HTTP_OK;

        /** @var SegmentManager $provider */
        $manager = $this->get('oro_segment.segment_manager');

        try {
            $result = $manager->getSegmentByEntityName($entityName, $term);
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }

    /**
     * Remove segment.
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Segment",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_segment_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroSegmentBundle:Segment"
     * )
     * @return Response
     */
    public function deleteAction($id)
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
        return $this->get('oro_segment.segment_manager.api');
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
