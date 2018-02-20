<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\NamePrefix("oro_api_workflow_entity_")
 */
class EntityController extends FOSRestController
{
    /**
     * @Rest\Get(
     *      "/api/rest/{version}/workflowentity",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Get entity with fields", resource=true)
     *
     * @return Response
     */
    public function getAction()
    {
        $statusCode = Codes::HTTP_OK;
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_workflow.entity_field_list_provider');
        try {
            $result = $provider->getFields(false, false, true, false, true, true);
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
