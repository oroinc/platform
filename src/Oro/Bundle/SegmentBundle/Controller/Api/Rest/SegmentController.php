<?php

namespace Oro\Bundle\SegmentBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Segment entity.
 */
class SegmentController extends RestController
{
    /**
     * Get entity segments.
     *
     * @ApiDoc(
     *      description="Get entity segments",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'entityName',
        requirements: '((\w+)_)+(\w+)',
        description: ' Entity full class name; backslashes (\) should be replaced with underscore (_).',
        nullable: false
    )]
    #[QueryParam(name: 'term', default: '', description: 'Search term', strict: false, nullable: true)]
    public function getItemsAction(Request $request)
    {
        $entityName = $this->container->get('oro_entity.routing_helper')
            ->resolveEntityClass($request->get('entityName'));
        $page = (int)$request->query->get('page', 1);
        $term = $request->query->get('term');
        $currentSegmentId = $request->query->get('currentSegment');
        if (null !== $currentSegmentId) {
            $currentSegmentId = (int)$currentSegmentId;
        }

        /** @var SegmentManager $provider */
        $manager = $this->container->get('oro_segment.segment_manager');

        $statusCode = Response::HTTP_OK;
        try {
            $result = $manager->getSegmentByEntityName($entityName, $term, $page, $currentSegmentId);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
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
     * @return Response
     */
    #[Acl(id: 'oro_segment_delete', type: 'entity', class: Segment::class, permission: 'DELETE')]
    public function deleteAction(int $id)
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
     * @return Response
     */
    #[AclAncestor('oro_segment_update')]
    public function postRunAction(int $id)
    {
        /** @var Segment $segment */
        $segment = $this->getManager()->find($id);
        if (!$segment) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        try {
            $this->container->get('oro_segment.static_segment_manager')->run($segment);
            return $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
        } catch (\LogicException $e) {
            return $this->handleView($this->view(null, Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_segment.segment_manager.api');
    }

    /**
     * @return FormInterface
     * @throws \RuntimeException
     */
    #[\Override]
    public function getForm()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }

    /**
     * @return ApiFormHandler
     * @throws \RuntimeException
     */
    #[\Override]
    public function getFormHandler()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }
}
