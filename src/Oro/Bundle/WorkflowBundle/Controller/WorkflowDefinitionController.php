<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @Route("/workflowdefinition")
 */
class WorkflowDefinitionController extends Controller
{
    /**
     * @Route(name="oro_workflow_definition_index")
     * @Template
     * @Acl(
     *      id="oro_workflow_definition_view",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route(
     *      "/create",
     *      name="oro_workflow_definition_create"
     * )
     * @Template("OroWorkflowBundle:WorkflowDefinition:update.html.twig")
     * @Acl(
     *      id="oro_workflow_definition_create",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="CREATE"
     * )
     * @return array
     */
    public function createAction()
    {
        return $this->updateAction(new WorkflowDefinition());
    }

    /**
     * @Route(
     *      "/update/{name}",
     *      name="oro_workflow_definition_update"
     * )
     * @Template("OroWorkflowBundle:WorkflowDefinition:update.html.twig")
     * @Acl(
     *      id="oro_workflow_definition_update",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="EDIT"
     * )
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     * @throws AccessDeniedHttpException
     */
    public function updateAction(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem()) {
            throw new AccessDeniedHttpException('System workflow definitions are not editable');
        }

        $form = $this->get('oro_workflow.form.workflow_definition');
        $form->setData($workflowDefinition);

        return array(
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities()
        );
    }

    /**
     * @Route(
     *      "/clone/{name}",
     *      name="oro_workflow_definition_clone"
     * )
     * @AclAncestor("oro_workflow_definition_create")
     * @Template("OroWorkflowBundle:WorkflowDefinition:update.html.twig")
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function cloneAction(WorkflowDefinition $workflowDefinition)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $clonePrefix = $translator->trans('oro.workflow.workflowdefinition.clone_label_prefix');

        $clonedDefinition = new WorkflowDefinition();
        $clonedDefinition->import($workflowDefinition)
            ->setName($workflowDefinition->getName() . uniqid('_clone_'))
            ->setLabel($clonePrefix . $workflowDefinition->getLabel())
            ->setSystem(false);

        return $this->updateAction($clonedDefinition);
    }
}
