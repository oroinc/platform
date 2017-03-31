<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandlerInterface;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * @Route("/workflowwidget")
 */
class WidgetController extends Controller
{
    /**
     * @Route("/entity-workflows/{entityClass}/{entityId}", name="oro_workflow_widget_entity_workflows")
     * @Template
     *
     * @param string $entityClass
     * @param int $entityId
     *
     * @return array
     */
    public function entityWorkflowsAction($entityClass, $entityId)
    {
        $entity = $this->getTransitionWidgetHelper()->getOrCreateEntityReference($entityClass, $entityId);
        if (!$entity) {
            throw $this->createNotFoundException(
                sprintf('Entity \'%s\' with id \'%d\' not found', $entityClass, $entityId)
            );
        }

        $workflowManager = $this->get('oro_workflow.registry.workflow_manager')->getManager();
        $applicableWorkflows = array_filter(
            $workflowManager->getApplicableWorkflows($entity),
            function (Workflow $workflow) use ($entity) {
                return $this->isWorkflowPermissionGranted('VIEW_WORKFLOW', $workflow->getName(), $entity);
            }
        );

        return [
            'entityId' => $entityId,
            'workflows' => array_map(
                function (Workflow $workflow) use ($entity) {
                    // extra case to show start transition (step name and disabled button)
                    // even if transitions performing is forbidden with ACL
                    $showDisabled = !$this->isWorkflowPermissionGranted(
                        'PERFORM_TRANSITIONS',
                        $workflow->getName(),
                        $entity
                    );

                    return $this->get('oro_workflow.workflow_data.provider')
                        ->getWorkflowData($entity, $workflow, $showDisabled);
                },
                $applicableWorkflows
            ),
            'originalUrl' => $this->get('oro_action.resolver.destination_page_resolver')->getOriginalUrl(),
        ];
    }

    /**
     * @Route(
     *      "/transition/create/attributes/{workflowName}/{transitionName}",
     *      name="oro_workflow_widget_start_transition_form"
     * )
     *
     * @param string $transitionName
     * @param string $workflowName
     * @param Request $request
     *
     * @return Response
     */
    public function startTransitionFormAction($transitionName, $workflowName, Request $request)
    {
        $entityId = $request->get('entityId', 0);
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);
        $entityClass = $workflow->getDefinition()->getRelatedEntity();
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $dataArray = [];

        if (!$transition->isEmptyInitOptions()) {
            $contextAttribute = $transition->getInitContextAttribute();
            $dataArray[$contextAttribute] = $this->get('oro_action.provider.button_search_context')
                ->getButtonSearchContext();
            $entityId = null;
        }

        $entity = $this->getTransitionWidgetHelper()->getOrCreateEntityReference($entityClass, $entityId);
        $workflowItem = $workflow->createWorkflowItem($entity, $dataArray);

        $transitionForm = $this->getTransitionWidgetHelper()->getTransitionForm($workflowItem, $transition);

        $saved = $this->getTransitionFormHandler($transition)
            ->processStartTransitionForm($transitionForm, $workflowItem, $transition, $request);

        $data = null;
        if ($saved) {
            $data = $this->getTransitionWidgetHelper()
                ->processWorkflowData($workflow, $transition, $transitionForm, $dataArray);

            $response = $this->get('oro_workflow.handler.start_transition_handler')
                ->handle($workflow, $transition, $data, $entity);

            if ($response) {
                return $response;
            }
        }

        $params = [
            'transition' => $transition,
            'data' => $data,
            'saved' => $saved,
            'workflowItem' => $workflowItem,
            'form' => $transitionForm->createView(),
            'formErrors' => $transitionForm->getErrors(true),
        ];

        return $this->render(
            $this->getTransitionWidgetHelper()->getTransitionFormTemplate($transition),
            array_merge($this->getPageFormData($transition, $workflowItem, $transitionForm, $request), $params)
        );
    }

    /**
     * @Route(
     *      "/transition/edit/attributes/{workflowItemId}/{transitionName}",
     *      name="oro_workflow_widget_transition_form"
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     *
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     * @param Request $request
     *
     * @return Response
     */
    public function transitionFormAction($transitionName, WorkflowItem $workflowItem, Request $request)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);

        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $transitionForm = $this->getTransitionWidgetHelper()->getTransitionForm($workflowItem, $transition);

        $saved = $this->getTransitionFormHandler($transition)
            ->processTransitionForm($transitionForm, $workflowItem, $transition, $request);

        if ($saved) {
            $response = $this->get('oro_workflow.handler.transition_handler')->handle($transition, $workflowItem);
            if ($response) {
                return $response;
            }
        }
        $params = [
            'transition' => $transition,
            'saved' => $saved,
            'workflowItem' => $workflowItem,
            'form' => $transitionForm->createView(),
            'formErrors' => $transitionForm->getErrors(true),
        ];

        return $this->render(
            $this->getTransitionWidgetHelper()->getTransitionFormTemplate($transition),
            array_merge($this->getPageFormData($transition, $workflowItem, $transitionForm, $request), $params)
        );
    }

    /**
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_workflow_widget_buttons")
     * @Template
     *
     * @param string $entityClass
     * @param int $entityId
     *
     * @return array
     */
    public function buttonsAction($entityClass, $entityId)
    {
        $workflowsData = [];

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $transitionDataProvider = $this->get('oro_workflow.transition_data.provider');
        $entity = $this->getTransitionWidgetHelper()->getOrCreateEntityReference($entityClass, $entityId);

        $workflows = $workflowManager->getApplicableWorkflows($entity);
        foreach ($workflows as $workflow) {
            $showDisabled = !$this->isWorkflowPermissionGranted('PERFORM_TRANSITIONS', $workflow->getName(), $entity);
            $workflowsData[$workflow->getName()] = [
                'label' => $workflow->getLabel(),
                'resetAllowed' => false,
                'transitionsData' => $transitionDataProvider
                    ->getAvailableStartTransitionsData($workflow, $entity, $showDisabled),
            ];
        }

        $workflowItems = $workflowManager->getWorkflowItemsByEntity($entity);
        foreach ($workflowItems as $workflowItem) {
            $name = $workflowItem->getWorkflowName();

            $workflowsData[$name]['transitionsData'] = $transitionDataProvider
                ->getAvailableTransitionsDataByWorkflowItem($workflowItem);
        }

        return [
            'entity_id' => $entityId,
            'workflowsData' => $workflowsData,
        ];
    }

    /**
     * @param string $permission
     * @param string $workflowName
     * @param object $entity
     *
     * @return bool
     */
    protected function isWorkflowPermissionGranted($permission, $workflowName, $entity)
    {
        $securityFacade = $this->container->get('oro_security.security_facade');

        return $securityFacade->isGranted(
            $permission,
            new DomainObjectWrapper($entity, new ObjectIdentity('workflow', $workflowName))
        );
    }

    /**
     * @param Transition $transition
     *
     * @return TransitionFormHandlerInterface|object
     */
    protected function getTransitionFormHandler(Transition $transition)
    {
        $handlerName = 'oro_workflow.handler.transition.form';
        if ($transition->hasFormConfiguration()) {
            $handlerName = 'oro_workflow.handler.transition.form.page_form';
        }

        return $this->get($handlerName);
    }

    /**
     * @param $providerAlias
     *
     * @return FormTemplateDataProviderInterface
     */
    protected function getFormTemplateDataProvider($providerAlias)
    {
        return $this->get('oro_form.registry.form_template_data_provider')
            ->get($providerAlias);
    }

    /**
     * @return TransitionWidgetHelper
     */
    protected function getTransitionWidgetHelper()
    {
        return $this->get('oro_workflow.helper.transition_widget');
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     * @param FormInterface $transitionForm
     * @param Request $request
     *
     * @return array
     */
    private function getPageFormData(
        Transition $transition,
        WorkflowItem $workflowItem,
        FormInterface $transitionForm,
        Request $request
    ) {
        if ($transition->hasFormConfiguration()) {
            return $this->getFormTemplateDataProvider($transition->getFormDataProvider())
                ->getData(
                    $workflowItem->getData()->get($transition->getFormDataAttribute()),
                    $transitionForm,
                    $request
                );
        }

        return [];
    }
}
