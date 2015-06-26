<?php

namespace Oro\Bundle\SegmentBundle\Controller\Api\Rest;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SegmentBundle\Entity\Segment;
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
     * @QueryParam(
     *      name="entityName",
     *      requirements="((\w+)_)+(\w+)",
     *      nullable=false,
     *      description=" Entity full class name; backslashes (\) should be replaced with underscore (_)."
     * )
     * @QueryParam(
     *      name="term", nullable=true, strict=false, default="",
     *      description="Search term"
     * )
     * @ApiDoc(
     *      description="Get entity segments",
     *      resource=true
     * )
     * @return Response
     */
    public function getItemsAction()
    {
        $entityName = $this->get('oro_entity.routing_helper')
            ->resolveEntityClass($this->getRequest()->get('entityName'));
        $page = $this->getRequest()->query->get('page', 1);
        $term = $this->getRequest()->query->get('term');
        $currentSegment = $this->getRequest()->query->get('currentSegment', null);
        $statusCode = Codes::HTTP_OK;

        /** @var SegmentManager $provider */
        $manager = $this->get('oro_segment.segment_manager');

        try {
            $result = $manager->getSegmentByEntityName($entityName, $term, (int)$page, $currentSegment);
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
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
     * Run static segment.
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Run Static Segment",
     *      resource=true
     * )
     * @AclAncestor("oro_segment_update")
     * @return Response
     */
    public function postRunAction($id)
    {
        /** @var Segment $segment */
        $segment = $this->getManager()->find($id);
        if (!$segment) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        try {
            $this->get('oro_segment.static_segment_manager')->run($segment);
            return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
        } catch (\LogicException $e) {
            return $this->handleView($this->view(null, Codes::HTTP_BAD_REQUEST));
        }
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
