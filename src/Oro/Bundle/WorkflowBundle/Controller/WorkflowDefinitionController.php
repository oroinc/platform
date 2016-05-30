<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

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
     *
     * @return array
     */
    public function indexAction()
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_workflow.workflow_definition.entity.class')
        );
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
     *
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
     *
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
            'workflowConfiguration' => $this->prepareConfiguration($workflowDefinition),
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities(),
            'delete_allowed' => true,
        );
    }

    /**
     * Prepares workflow configuration to display. Translates attribute labels.
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    protected function prepareConfiguration(WorkflowDefinition $workflowDefinition)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $configuration = $workflowDefinition->getConfiguration();

        if (isset($configuration['attributes'])) {
            foreach ($configuration['attributes'] as $attrName => $attrConfig) {
                $configuration['attributes'][$attrName]['translated_label'] = $translator->trans($attrConfig['label']);
            }
        }
        return $configuration;
    }

    /**
     * @Route(
     *      "/view/{name}",
     *      name="oro_workflow_definition_view"
     * )
     * @AclAncestor("oro_workflow_definition_view")
     * @Template("OroWorkflowBundle:WorkflowDefinition:view.html.twig")
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function viewAction(WorkflowDefinition $workflowDefinition)
    {
        return array(
            'entity' => $workflowDefinition,
            'workflowConfiguration' => $this->prepareConfiguration($workflowDefinition),
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities()
        );
    }

    /**
     * @Route(
     *      "/info/{name}",
     *      name="oro_workflow_definition_info"
     * )
     * @AclAncestor("oro_workflow_definition_view")
     * @Template
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function infoAction(WorkflowDefinition $workflowDefinition)
    {
        return array(
            'entity' => $workflowDefinition
        );
    }
}
