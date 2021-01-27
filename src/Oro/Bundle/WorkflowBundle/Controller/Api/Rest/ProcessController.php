<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API Process Controller
 * @Rest\NamePrefix("oro_api_process_")
 */
class ProcessController extends FOSRestController
{
    /**
     * Activate process
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Rest\Post(
     *      "/api/rest/{version}/process/activate/{processDefinition}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
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
     * @Rest\Post(
     *      "/api/rest/{version}/process/deactivate/{processDefinition}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
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
