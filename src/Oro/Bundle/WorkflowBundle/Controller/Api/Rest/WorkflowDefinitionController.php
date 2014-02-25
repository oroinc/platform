<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Rest\NamePrefix("oro_api_workflow_definition")
 */
class WorkflowDefinitionController extends FOSRestController
{
    /**
     * Delete workflow definition
     *
     * Returns
     * - HTTP_NO_CONTENT (204)
     *
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
            $em = $this->getDoctrine()->getManager();
            $em->remove($workflowDefinition);
            $em->flush();
            return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
        }
    }
}
