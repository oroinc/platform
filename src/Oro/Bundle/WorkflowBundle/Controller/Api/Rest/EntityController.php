<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get entity with fields.
 */
class EntityController extends AbstractFOSRestController
{
    /**
     * @ApiDoc(description="Get entity with fields", resource=true)
     *
     * @return Response
     */
    public function getAction()
    {
        $statusCode = Response::HTTP_OK;
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_workflow.entity_field_list_provider');
        try {
            $result = $provider->getFields(false, false, true, false, true, true);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
