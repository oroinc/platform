<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
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

        $entityFields = [];
        if (null !== $workflowDefinition->getRelatedEntity()) {
            /* @var $provider EntityWithFieldsProvider */
            $provider = $this->get('oro_entity.entity_field_list_provider');
            $entityFields = $provider->getFields(false, false, true, false, true, true);
        }

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities(),
            'delete_allowed' => true,
            'translateLinks' => $translateLinks,
            'entityFields' => $entityFields,
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
     * @throws AccessDeniedHttpException
     */
    public function configureAction(Request $request, WorkflowDefinition $workflowDefinition)
    {
        $workflow = $this->get('oro_workflow.manager.system')->getWorkflow($workflowDefinition->getName());
        if (!count($workflow->getVariables())) {
            throw new AccessDeniedHttpException();
        }

        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition);
        $translateLinks = $this->getTranslationsDatagridLinksProvider()->getWorkflowTranslateLinks($workflowDefinition);
        $form = $this->createForm(WorkflowVariablesType::NAME, null, [
            'workflow' => $workflow,
        ]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $workflowVarHandler = $this->get('oro_workflow.handler.workflow_variables');
            $workflowVarHandler->updateWorkflowVariables($workflowDefinition, $form->getData());
            $this->addFlash('success', $this->get('translator')->trans('oro.workflow.variable.save.success_message'));

            return $this->get('oro_ui.router')->redirect($workflowDefinition);
        }

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'translateLinks' => $translateLinks,
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
        $this->getTranslationProcessor()->translateWorkflowDefinitionFields($workflowDefinition);
        $workflow = $this->get('oro_workflow.manager.system')->getWorkflow($workflowDefinition->getName());

        return [
            'entity' => $workflowDefinition,
            'system_entities' => $this->get('oro_entity.entity_provider')->getEntities(),
            'translateLinks' => $translateLinks,
            'variables' => $workflow->getVariables(true),
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
        $response['workflowsToDeactivation'] = $workflowsToDeactivation->getValues();

        if ($form->isValid()) {
            $workflowManager = $this->get('oro_workflow.registry.workflow_manager')->getManager();
            $workflowNames = array_merge(
                $form->getData(),
                $workflowsToDeactivation->map(
                    function (Workflow $workflow) {
                        return $workflow->getName();
                    }
                )->getValues()
            );

            $translator = $this->get('translator');

            $deactivated = [];
            foreach ($workflowNames as $workflowName) {
                if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                    $workflow = $workflowManager->getWorkflow($workflowName);

                    $workflowManager->resetWorkflowData($workflow->getName());
                    $workflowManager->deactivateWorkflow($workflow->getName());

                    $deactivated[] = $translator->trans(
                        $workflow->getLabel(),
                        [],
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN
                    );
                }
            }

            try {
                $workflowManager->activateWorkflow($workflowDefinition->getName());

                $response['deactivated'] = $deactivated;
            } catch (\RuntimeException $e) {
                $response['error'] = $e->getMessage();
                unset($response['savedId']);
            }
        }

        return $response;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return Workflow[]|Collection
     */
    protected function getWorkflowsToDeactivation(WorkflowDefinition $workflowDefinition)
    {
        $workflows = $this->get('oro_workflow.registry.system')
            ->getActiveWorkflowsByActiveGroups($workflowDefinition->getExclusiveActiveGroups());

        return $workflows->filter(
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
