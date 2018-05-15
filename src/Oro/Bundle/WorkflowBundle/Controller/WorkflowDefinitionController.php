<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\TranslationsDatagridLinksProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @throws AccessDeniedException
     */
    public function updateAction(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem() || !$this->isEditable($workflowDefinition)) {
            throw new AccessDeniedException('System workflow definitions are not editable');
        }
        $translateLinks = $this->getTranslationsDatagridLinksProvider()->getWorkflowTranslateLinks($workflowDefinition);
        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition, true);

        $form = $this->get('oro_workflow.form.workflow_definition');
        $form->setData($workflowDefinition);

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'delete_allowed' => true,
            'translateLinks' => $translateLinks,
            'availableDestinations' => DestinationPageResolver::AVAILABLE_DESTINATIONS,
        ];
    }

    /**
     * @Route(
     *      "/configure/{name}",
     *      name="oro_workflow_definition_configure"
     * )
     * @Template()
     * @Acl(
     *      id="oro_workflow_definition_configure",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="CONFIGURE"
     * )
     *
     * @param Request $request
     * @param WorkflowDefinition $workflowDefinition
     *
     * @return array
     * @throws AccessDeniedException
     */
    public function configureAction(Request $request, WorkflowDefinition $workflowDefinition)
    {
        $workflow = $this->get('oro_workflow.manager.system')->getWorkflow($workflowDefinition->getName());
        if (!count($workflow->getVariables())) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(WorkflowVariablesType::class, null, ['workflow' => $workflow]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $workflowVarHandler = $this->get('oro_workflow.handler.workflow_variables');
                $workflowVarHandler->updateWorkflowVariables($workflowDefinition, $form->getData());

                $this->addFlash(
                    'success',
                    $this->get('translator')->trans('oro.workflow.variable.save.success_message')
                );

                return $this->get('oro_ui.router')->redirect($workflowDefinition);
            }
        }

        $translateLinksProvider = $this->getTranslationsDatagridLinksProvider();

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'translateLinks' => $translateLinksProvider->getWorkflowTranslateLinks($workflowDefinition),
        ];
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
        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition, true);
        $workflow = $this->get('oro_workflow.manager.system')->getWorkflow($workflowDefinition->getName());

        return [
            'entity' => $workflowDefinition,
            'translateLinks' => $translateLinks,
            'variables' => $workflow->getVariables(true),
            'edit_allowed' => $this->isEditable($workflowDefinition)
        ];
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     */
    protected function isEditable(WorkflowDefinition $workflowDefinition)
    {
        $checker = $this->get('oro_workflow.configuration.checker');

        return $checker->isClean($workflowDefinition->getConfiguration());
    }

    /**
     * Activate WorkflowDefinition form
     *
     * @Route("/activate-form/{name}", name="oro_workflow_definition_activate_from_widget")
     * @AclAncestor("oro_workflow_definition_update")
     * @Template("OroWorkflowBundle:WorkflowDefinition:widget/activateForm.html.twig")
     *
     * @param Request $request
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function activateFormAction(Request $request, WorkflowDefinition $workflowDefinition)
    {
        $form = $this->createForm(WorkflowReplacementType::class, null, ['workflow' => $workflowDefinition]);
        $response = ['form' => $form->createView()];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workflowManager = $this->get('oro_workflow.registry.workflow_manager')->getManager();
            $helper = $this->get('oro_workflow.helper.workflow_deactivation');
            $data = $form->getData();

            try {
                $workflows = array_merge(
                    $data['workflowsToDeactivation'],
                    $helper->getWorkflowsToDeactivation($workflowDefinition)
                        ->map(
                            function (Workflow $workflow) {
                                return $workflow->getName();
                            }
                        )->getValues()
                );

                $response['deactivated'] = $this->deactivateWorkflows($workflowManager, $workflows);

                $workflowManager->activateWorkflow($workflowDefinition->getName());

                $response['savedId'] = $workflowDefinition->getName();
            } catch (\RuntimeException $e) {
                $response['error'] = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * @param WorkflowManager $workflowManager
     * @param array $workflowNames
     * @return array
     */
    private function deactivateWorkflows(WorkflowManager $workflowManager, array $workflowNames)
    {
        $deactivated = [];
        /* @var $translationHelper WorkflowTranslationHelper */
        $translationHelper = $this->get('oro_workflow.helper.translation');

        foreach ($workflowNames as $workflowName) {
            if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                $workflow = $workflowManager->getWorkflow($workflowName);

                $workflowManager->resetWorkflowData($workflow->getName());
                $workflowManager->deactivateWorkflow($workflow->getName());

                $deactivated[] = $translationHelper->findWorkflowTranslation(
                    $workflow->getLabel(),
                    $workflow->getName()
                );
            }
        }

        return $deactivated;
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
