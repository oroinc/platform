<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Tools\WorkflowStepHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

/**
 * @Route("/workflowwidget")
 */
class WidgetController extends Controller
{
    const DEFAULT_TRANSITION_TEMPLATE = 'OroWorkflowBundle:Widget:widget/transitionForm.html.twig';

    /**
     * @Route("/entity-workflows/{entityClass}/{entityId}", name="oro_workflow_widget_entity_workflows")
     * @Template
     * @AclAncestor("oro_workflow")
     *
     * @param string $entityClass
     * @param int $entityId
     * @return array
     */
    public function entityWorkflowsAction($entityClass, $entityId)
    {
        $entity = $this->getEntityReference($entityClass, $entityId);
        if (!$entity) {
            throw $this->createNotFoundException(
                sprintf('Entity \'%s\' with id \'%d\' not found', $entityClass, $entityId)
            );
        }

        $workflowManager = $this->get('oro_workflow.manager');

        return [
            'entityId' => $entityId,
            'workflows' => array_map(
                function (Workflow $workflow) use ($entity, $workflowManager) {
                    return $this->getWorkflowData($entity, $workflow, $workflowManager);
                },
                $workflowManager->getApplicableWorkflows($entity)
            )
        ];
    }

    /**
     * @Route(
     *      "/transition/create/attributes/{workflowName}/{transitionName}",
     *      name="oro_workflow_widget_start_transition_form"
     * )
     * @AclAncestor("oro_workflow")
     *
     * @param Request $request
     * @param string $transitionName
     * @param string $workflowName
     * @return Response
     */
    public function startTransitionFormAction(Request $request, $transitionName, $workflowName)
    {
        $entityId = $request->get('entityId', 0);

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);
        $entityClass = $workflow->getDefinition()->getRelatedEntity();

        $entity = $this->getEntityReference($entityClass, $entityId);

        $workflowItem = $workflow->createWorkflowItem($entity);
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $transitionForm = $this->getTransitionForm($workflowItem, $transition);

        $data = null;
        $saved = false;
        if ($request->isMethod('POST')) {
            $transitionForm->submit($request);

            if ($transitionForm->isValid()) {
                // Create new WorkflowData instance with all data required to start.
                // Original WorkflowData can not be used, as some attributes may be set by reference
                // So, serialized data will not contain all required data.
                $formOptions = $transition->getFormOptions();
                $attributes = array_keys($formOptions['attribute_fields']);

                $formAttributes = $workflowItem->getData()->getValues($attributes);
                foreach ($formAttributes as $value) {
                    // Need to persist all new entities to allow serialization
                    // and correct passing to API start method of all input data.
                    // Form validation already performed, so all these entities are valid
                    // and they can be used in workflow start action.
                    if (is_object($value) && $doctrineHelper->isManageableEntity($value)) {
                        $entityManager = $doctrineHelper->getEntityManager($value);
                        $unitOfWork = $entityManager->getUnitOfWork();
                        if (!$unitOfWork->isInIdentityMap($value) || $unitOfWork->isScheduledForInsert($value)) {
                            $entityManager->persist($value);
                            $entityManager->flush($value);
                        }
                    }
                }

                /** @var WorkflowAwareSerializer $serializer */
                $serializer = $this->get('oro_workflow.serializer.data.serializer');
                $serializer->setWorkflowName($workflow->getName());
                $data = $serializer->serialize(new WorkflowData($formAttributes), 'json');
                $saved = true;

                $response = $this->get('oro_workflow.handler.start_transition_handler')
                    ->handle($workflow, $transition, $data, $entity);
                if ($response) {
                    return $response;
                }
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
     * @AclAncestor("oro_workflow")
     *
     * @param Request $request
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function transitionFormAction(Request $request, $transitionName, WorkflowItem $workflowItem)
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
     * @param object $entity
     * @param Workflow $workflow
     * @param WorkflowManager $workflowManager
     * @return array
     */
    protected function getWorkflowData($entity, Workflow $workflow, WorkflowManager $workflowManager)
    {
        $workflowItem = $workflowManager->getWorkflowItem($entity, $workflow->getName());
        $currentStep = $workflowItem ? $workflowItem->getCurrentStep() : null;

        $isStepsDisplayOrdered = $workflow->getDefinition()->isStepsDisplayOrdered();

        if ($isStepsDisplayOrdered) {
            $steps = $workflow->getStepManager()->getOrderedSteps()->toArray();
        } elseif ($currentStep) {
            $steps = [$workflow->getStepManager()->getStep($currentStep->getName())];
        } else {
            $steps = $this->getNextSteps($workflow);
        }

        $helper = new WorkflowStepHelper($workflow);

        $steps = array_map(
            function (Step $step) use ($currentStep, $helper) {
                return [
                    'label' => $step->getLabel(),
                    'active' => $currentStep && $step->getName() === $currentStep->getName(),
                    'possibleStepsCount' => count($helper->getStepsAfter($step))
                ];
            },
            $steps
        );

        $transitionData = $workflowItem
            ? $this->getAvailableTransitionsDataByWorkflowItem($workflowItem)
            : $this->getAvailableStartTransitionsData($workflow, $entity);

        return [
            'name' => $workflow->getName(),
            'label' => $workflow->getLabel(),
            'isStarted' => $workflowItem !== null,
            'stepsData' => [
                'is_ordered' => $isStepsDisplayOrdered,
                'steps' => $steps
            ],
            'transitionsData' => $transitionData
        ];
    }

    /**
     * @param Workflow $workflow
     * @return array|Step[]
     */
    protected function getNextSteps(Workflow $workflow)
    {
        $startTransitions = $workflow->getTransitionManager()->getStartTransitions();
        $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();

        if (count($startTransitions) > 1 && $defaultStartTransition) {
            $startTransitions = array_filter(
                $startTransitions->toArray(),
                function (Transition $transition) use ($defaultStartTransition) {
                    return $transition->getName() !== $defaultStartTransition->getName();
                }
            );
        } else {
            $startTransitions = $startTransitions->toArray();
        }

        return array_map(
            function (Transition $transition) use ($startTransitions) {
                return $transition->getStepTo();
            },
            $startTransitions
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
     * Get transitions data for view based on workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @return array
     */
    protected function getAvailableTransitionsDataByWorkflowItem(WorkflowItem $workflowItem)
    {
        $transitionsData = [];
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $transitions = $workflowManager->getTransitionsByWorkflowItem($workflowItem);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
                if ($isAllowed || !$transition->isUnavailableHidden()) {
                    $transitionsData[$transition->getName()] = [
                        'workflow' => $workflowManager->getWorkflow($workflowItem),
                        'workflowItem' => $workflowItem,
                        'transition' => $transition,
                        'isAllowed' => $isAllowed,
                        'errors' => $errors
                    ];
                }
            }
        }
        return $transitionsData;
    }

    /**
     * Get start transitions data for view based on workflow and entity.
     *
     * @param Workflow $workflow
     * @param object $entity
     * @return array
     */
    protected function getAvailableStartTransitionsData(Workflow $workflow, $entity)
    {
        $transitionsData = [];
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');

        $transitions = $workflowManager->getStartTransitions($workflow);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $transitionData = $this->getStartTransitionData($workflow, $transition, $entity);
                if ($transitionData !== null) {
                    $transitionsData[$transition->getName()] = $transitionData;
                }
            }
        }

        // extra case to show start transition
        if (empty($transitionsData) && $workflow->getStepManager()->hasStartStep()) {
            $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();
            if ($defaultStartTransition) {
                $startTransitionData = $this->getStartTransitionData($workflow, $defaultStartTransition, $entity);
                if ($startTransitionData !== null) {
                    $transitionsData[$defaultStartTransition->getName()] = $startTransitionData;
                }
            }
        }

        return $transitionsData;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     * @return array|null
     */
    protected function getStartTransitionData(Workflow $workflow, Transition $transition, $entity)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');

        $errors = new ArrayCollection();
        $isAllowed = $workflowManager
            ->isStartTransitionAvailable($workflow, $transition, $entity, [], $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return [
                'workflow' => $workflowManager->getWorkflow($workflow),
                'transition' => $transition,
                'isAllowed' => $isAllowed,
                'errors' => $errors
            ];
        }

        return null;
    }

    /**
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     * @throws BadRequestHttpException
     * @return mixed
     */
    protected function getEntityReference($entityClass, $entityId)
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
}
