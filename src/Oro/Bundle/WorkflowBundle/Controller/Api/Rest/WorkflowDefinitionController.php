<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
        /*
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if ($this->processForm($entity)) {
            $view = $this->view(null, Codes::HTTP_NO_CONTENT);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }
        */

        return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
    }

    /**
     * Create new workflow definition
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
            $em = $this->getEntityManager();
            $em->remove($workflowDefinition);
            $em->flush();
            return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroWorkflowBundle:WorkflowDefinition');
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        return $this->getRequest()->request->all();
    }
}
