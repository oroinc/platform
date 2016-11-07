<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\TranslationsDatagridLinksProvider;

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
        return [
            'entity_class' => $this->container->getParameter('oro_workflow.entity.workflow_definition.class')
        ];
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
        $translateLinks = $this->getTranslationsDatagridLinksProvider()->getWorkflowTranslateLinks($workflowDefinition);
        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition);

        $form = $this->get('oro_workflow.form.workflow_definition');
        $form->setData($workflowDefinition);

        return array(
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities(),
            'delete_allowed' => true,
            'translateLinks' => $translateLinks,
        );
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
        $translateLinks = $this->getTranslationsDatagridLinksProvider()->getWorkflowTranslateLinks($workflowDefinition);
        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition);

        return [
            'entity' => $workflowDefinition,
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities(),
            'translateLinks' => $translateLinks,
        ];
    }

    /**
     * Activate WorkflowDefinition form
     *
     * @Route("/activate-form/{name}", name="oro_workflow_definition_activate_from_widget")
     * @AclAncestor("oro_workflow_definition_update")
     * @Template("OroWorkflowBundle:WorkflowDefinition:widget/activateForm.html.twig")
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function activateFormAction(WorkflowDefinition $workflowDefinition)
    {
        $form = $this->createForm(
            WorkflowReplacementSelectType::NAME,
            null,
            ['workflow' => $workflowDefinition->getName()]
        );

        $workflowsToDeactivation = $this->getWorkflowsToDeactivation($workflowDefinition);

        $response = $this->get('oro_form.model.update_handler')->update($workflowDefinition, $form, null);
        $response['workflow'] = $workflowDefinition->getName();
        $response['workflowsToDeactivation'] = $workflowsToDeactivation;

        if ($form->isValid()) {
            $translator = $this->get('translator');

            $workflowManager = $this->get('oro_workflow.manager');
            $workflowNames = array_merge(
                $form->getData(),
                array_map(
                    function (Workflow $workflow) use ($translator) {
                        return $translator->trans(
                            $workflow->getName(),
                            [],
                            WorkflowTranslationHelper::TRANSLATION_DOMAIN
                        );
                    },
                    $workflowsToDeactivation
                )
            );

            $deactivated = [];
            foreach ($workflowNames as $workflowName) {
                if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                    $workflow = $workflowManager->getWorkflow($workflowName);

                    $workflowManager->resetWorkflowData($workflow->getName());
                    $workflowManager->deactivateWorkflow($workflow->getName());

                    $deactivated[] = $workflow->getLabel();
                }
            }

            $response['deactivated'] = $deactivated;

            $workflowManager->activateWorkflow($workflowDefinition->getName());
        }

        return $response;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array|Workflow[]
     */
    protected function getWorkflowsToDeactivation(WorkflowDefinition $workflowDefinition)
    {
        $workflows = $this->get('oro_workflow.registry')
            ->getActiveWorkflowsByActiveGroups($workflowDefinition->getExclusiveActiveGroups());

        return array_filter(
            $workflows,
            function (Workflow $workflow) use ($workflowDefinition) {
                return $workflow->getName() !== $workflowDefinition->getName();
            }
        );
    }

    /**
     * @return TranslationsDatagridLinksProvider
     */
    protected function getTranslationsDatagridLinksProvider()
    {
        return $this->get('oro_workflow.translation.translations_datagrid_links_provider');
    }

    /**
     * @return TranslationProcessor
     */
    protected function getTranslationProcessor()
    {
        return $this->get('oro_workflow.translation.processor');
    }
}
