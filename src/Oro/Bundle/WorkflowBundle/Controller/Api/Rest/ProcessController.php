<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for Process entity.
 */
class ProcessController extends AbstractFOSRestController
{
    /**
     * Activate process
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Activate process", resource=true)
     * @Acl(
     *      id="oro_process_definition_update",
     *      type="entity",
     *      class="OroWorkflowBundle:ProcessDefinition",
     *      permission="EDIT"
     * )
     *
     * @param ProcessDefinition $processDefinition
     * @return Response
     */
    public function activateAction(ProcessDefinition $processDefinition)
    {
        $processDefinition->setEnabled(true);

        $entityManager = $this->getManager();
        $entityManager->persist($processDefinition);
        $entityManager->flush();

        return $this->handleView(
            $this->view(
                array(
                    'message'    => $this->get('translator')->trans('oro.workflow.notification.process.activated'),
                    'successful' => true,
                ),
                Response::HTTP_OK
            )
        );
    }

    /**
     * Deactivate process
     *
     * Returns
     * - HTTP_OK (204)
     *
     * @ApiDoc(description="Deactivate process", resource=true)
     * @AclAncestor("oro_process_definition_update")
     *
     * @param ProcessDefinition $processDefinition
     * @return Response
     */
    public function deactivateAction(ProcessDefinition $processDefinition)
    {
        $processDefinition->setEnabled(false);

        $entityManager = $this->getManager();
        $entityManager->persist($processDefinition);
        $entityManager->flush();

        return $this->handleView(
            $this->view(
                array(
                    'message'    => $this->get('translator')->trans('oro.workflow.notification.process.deactivated'),
                    'successful' => true,
                ),
                Response::HTTP_OK
            )
        );
    }

    /**
     * Get entity Manager
     *
     * @return \Doctrine\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroWorkflowBundle:ProcessDefinition');
    }
}
