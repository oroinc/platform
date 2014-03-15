<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\Rest\Util\Codes;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\WorkflowBundle\Field\FieldProvider;

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
     * @ApiDoc(description="Get list of fields by entity", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param string $entityName
     * @return Response
     */
    public function getAction($entityName)
    {
        $entityName        = str_replace('_', '\\', $entityName);
        $withRelations     = ('1' == $this->getRequest()->query->get('with-relations'));
        $withEntityDetails = ('1' == $this->getRequest()->query->get('with-entity-details'));
        $deepLevel         = $this->getRequest()->query->has('deep-level')
            ? (int)$this->getRequest()->query->get('deep-level')
            : 0;
        $lastDeepLevelRelations = ('1' == $this->getRequest()->query->get('last-deep-level-relations'));

        $statusCode = Codes::HTTP_OK;
        /** @var FieldProvider $provider */
        $provider = $this->get('oro_workflow.field_provider');
        try {
            $result = $provider->getFields(
                $entityName,
                $withRelations,
                $withEntityDetails,
                $deepLevel,
                $lastDeepLevelRelations
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
