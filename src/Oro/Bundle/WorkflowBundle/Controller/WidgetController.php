<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * @Route("/workflowwidget")
 */
class WidgetController extends Controller
{
    const DEFAULT_TRANSITION_TEMPLATE = 'OroWorkflowBundle:Widget:widget/transitionForm.html.twig';

    /**
     * @Route("/entity-workflows/{entityClass}/{entityId}", name="oro_workflow_widget_entity_workflows")
     * @Template
     *
     * @param string $entityClass
     * @param int $entityId
     * @return array
     */
    public function entityWorkflowsAction($entityClass, $entityId)
    {
        $entity = $this->getOrCreateEntityReference($entityClass, $entityId);
        if (!$entity) {
            throw $this->createNotFoundException(
                sprintf('Entity \'%s\' with id \'%d\' not found', $entityClass, $entityId)
            );
        }

        $workflowManager = $this->get('oro_workflow.manager');
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
            )
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

        $entity = $this->getOrCreateEntityReference($entityClass, $entityId);
        $workflowItem = $workflow->createWorkflowItem($entity, $dataArray);
        $transitionForm = $this->getTransitionForm($workflowItem, $transition);
        $formOptions = $transition->getFormOptions();

        $attributeNames = array_keys($formOptions['attribute_fields']);

        $saved = $this->get('oro_workflow.handler.transition.form')
            ->handleTransitionForm($transitionForm, $attributeNames);

        $data = null;
        if ($saved) {
            $formAttributes = $transitionForm->getData()->getValues($attributeNames);

            $serializer = $this->get('oro_workflow.serializer.data.serializer');
            $serializer->setWorkflowName($workflow->getName());
            $data = $serializer->serialize(new WorkflowData(array_merge($formAttributes, $dataArray)), 'json');
            $response = $this->get('oro_workflow.handler.start_transition_handler')
                ->handle($workflow, $transition, $data, $entity);

            if ($response) {
                return $response;
            }
        }

        return $this->render(
            $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'data' => $data,
                'saved' => $saved,
                'workflowItem' => $workflowItem,
                'form' => $transitionForm->createView(),
            ]
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
     * @return Response
     */
    public function transitionFormAction($transitionName, WorkflowItem $workflowItem, Request $request)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);

        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $transitionForm = $this->getTransitionForm($workflowItem, $transition);

        $saved = false;
        if ($request->isMethod('POST')) {
            $transitionForm->submit($request);

            if ($transitionForm->isValid()) {
                $workflowItem->setUpdated();
                $this->getEntityManager()->flush();

                $saved = true;

                $response = $this->get('oro_workflow.handler.transition_handler')->handle($transition, $workflowItem);
                if ($response) {
                    return $response;
                }
            }
        }

        return $this->render(
            $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'saved' => $saved,
                'workflowItem' => $workflowItem,
                'form' => $transitionForm->createView(),
            ]
        );
    }

    /**
     * Get transition form.
     *
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     * @return Form
     */
    protected function getTransitionForm(WorkflowItem $workflowItem, Transition $transition)
    {
        return $this->createForm(
            $transition->getFormType(),
            $workflowItem->getData(),
            array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                ]
            )
        );
    }

    /**
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_workflow_widget_buttons")
     * @Template
     *
     * @param string $entityClass
     * @param int $entityId
     * @return array
     */
    public function buttonsAction($entityClass, $entityId)
    {
        $workflowsData = [];

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $transitionDataProvider = $this->get('oro_workflow.transition_data.provider');
        $entity = $this->getOrCreateEntityReference($entityClass, $entityId);

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
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     * @throws BadRequestHttpException
     * @return mixed
     */
    protected function getOrCreateEntityReference($entityClass, $entityId = null)
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');
        try {
            if ($entityId) {
                $entity = $doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroWorkflowBundle:WorkflowItem');
    }

    /**
     * @param string $permission
     * @param string $workflowName
     * @param object $entity
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
}
