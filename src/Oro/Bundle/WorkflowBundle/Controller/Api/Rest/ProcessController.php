<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     *      class="Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition",
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
                    'message'    => $this->container->get('translator')
                        ->trans('oro.workflow.notification.process.activated'),
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
                    'message'    => $this->container->get('translator')
                        ->trans('oro.workflow.notification.process.deactivated'),
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
        return $this->container->get('doctrine')->getManagerForClass(ProcessDefinition::class);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'doctrine' => ManagerRegistry::class,
                'translator' => TranslatorInterface::class,
            ]
        );
    }
}
