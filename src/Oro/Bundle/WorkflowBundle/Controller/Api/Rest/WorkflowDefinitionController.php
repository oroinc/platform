<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionHandleBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;

/**
 * @Rest\NamePrefix("oro_api_workflow_definition_")
 */
class WorkflowDefinitionController extends FOSRestController
{
    /**
     * REST GET item
     *
     * @param WorkflowDefinition $workflowDefinition
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflowdefinition/{workflowDefinition}",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(
     *      description="Get workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_view")
     * @return Response
     */
    public function getAction(WorkflowDefinition $workflowDefinition)
    {
        return $this->handleView($this->view($workflowDefinition, Codes::HTTP_OK));
    }

    /**
     * Update workflow definition
     *
     * Returns
     * - HTTP_OK (200)
     * - HTTP_BAD_REQUEST (400)
     *
     * @param WorkflowDefinition $workflowDefinition
     *
     * @Rest\Put(
     *      "/api/rest/{version}/workflowdefinition/{workflowDefinition}",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(
     *      description="Update workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_update")
     * @return Response
     */
    public function putAction(WorkflowDefinition $workflowDefinition)
    {
        try {
            /** @var WorkflowDefinitionHandleBuilder $definitionBuilder */
            $definitionBuilder = $this->get('oro_workflow.configuration.builder.workflow_definition.handle');
            $builtDefinition = $definitionBuilder->buildFromRawConfiguration($this->getConfiguration());
            $this->getHandler()->updateWorkflowDefinition($workflowDefinition, $builtDefinition);
        } catch (\Exception $exception) {
            return $this->handleView(
                $this->view(
                    array('error' => $exception->getMessage()),
                    Codes::HTTP_BAD_REQUEST
                )
            );
        }

        return $this->handleView($this->view($workflowDefinition->getName(), Codes::HTTP_OK));
    }

    /**
     * Create new workflow definition
     *
     * @param WorkflowDefinition $workflowDefinition
     *
     * @Rest\Post(
     *      "/api/rest/{version}/workflowdefinition/{workflowDefinition}",
     *      defaults={"version"="latest", "_format"="json", "workflowDefinition"=null}
     * )
     * @ApiDoc(
     *      description="Create new workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_create")
     * @return Response
     */
    public function postAction(WorkflowDefinition $workflowDefinition = null)
    {
        if (!$workflowDefinition) {
            $workflowDefinition = new WorkflowDefinition();
        }

        return $this->putAction($workflowDefinition);
    }

    /**
     * Delete workflow definition
     *
     * Returns
     * - HTTP_NO_CONTENT (204)
     * - HTTP_FORBIDDEN (403)
     *
     * @Rest\Delete(
     *      "/api/rest/{version}/workflowdefinition/{workflowDefinition}",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Delete workflow definition")
     * @Acl(
     *      id="oro_workflow_definition_delete",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="DELETE"
     * )
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function deleteAction(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem()) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        } else {
            $this->getHandler()->deleteWorkflowDefinition($workflowDefinition);

            return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
        }
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        return $this->getRequest()->request->all();
    }

    /**
     * @return WorkflowDefinitionHandler
     */
    protected function getHandler()
    {
        return $this->get('oro_workflow.handler.workflow_definition');
    }
}
