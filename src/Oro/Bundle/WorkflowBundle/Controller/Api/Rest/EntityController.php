<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\Rest\Util\Codes;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

/**
 * @Rest\NamePrefix("oro_api_workflow_")
 */
class EntityController extends FOSRestController
{
    /**
     * @Rest\Get(
     *      "/api/rest/{version}/workflowentity/{entityName}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Get entity with fields", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param string $entityName
     * @return Response
     */
    public function getAction($entityName)
    {
        $withRelations      = ('1' == $this->getRequest()->query->get('with-relations'));

        $statusCode = Codes::HTTP_OK;
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_workflow.field_list_provider');
        try {
            $result = $provider->getFields(
                false,
                false,
                $withRelations
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
