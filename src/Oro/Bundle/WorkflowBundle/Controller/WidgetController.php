<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
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
     * @Route("/steps/{entityClass}/{entityId}", name="oro_workflow_widget_steps")
     * @Template
     * @AclAncestor("oro_workflow")
     */
    public function stepsAction($entityClass, $entityId)
    {
        $entity = $this->getEntityReference($entityClass, $entityId);

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflowItem    = $workflowManager->getWorkflowItemByEntity($entity);

        $steps = array();
        $currentStep = null;
        if ($workflowItem) {
            $workflow = $workflowManager->getWorkflow($workflowItem);

            if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
                $steps = $workflow->getStepManager()->getOrderedSteps();
            } else {
                $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
            }

            $currentStep = $workflowItem->getCurrentStep();
        }

        $steps = $steps->map(function ($step) {
            return array(
                'name' => $step->getName(),
                'label' => $step->getLabel()
            );
        });

        $steps = $steps->toArray();

        return array(
            'steps' => $steps,
            'currentStep' => array(
                'name' => $currentStep->getName()
            )
        );
    }

    /**
     * @Route(
     *      "/transition/create/attributes/{workflowName}/{transitionName}",
     *      name="oro_workflow_widget_start_transition_form"
     * )
     * @AclAncestor("oro_workflow")
     * @param string $transitionName
     * @param string $workflowName
     * @return array
     * @throws BadRequestHttpException
     */
    public function startTransitionFormAction($transitionName, $workflowName)
    {
        $entityId = $this->getRequest()->get('entityId', 0);

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
        if ($this->getRequest()->isMethod('POST')) {
            $transitionForm->submit($this->getRequest());

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
            array(
                'transition' => $transition,
                'data' => $data,
                'saved' => $saved,
                'workflowItem' => $workflowItem,
                'form' => $transitionForm->createView(),
            )
        );
    }

    /**
     * @Route(
     *      "/transition/edit/attributes/{workflowItemId}/{transitionName}",
     *      name="oro_workflow_widget_transition_form"
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @AclAncestor("oro_workflow")
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     * @return array
     */
    public function transitionFormAction($transitionName, WorkflowItem $workflowItem)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);

        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        $transitionForm = $this->getTransitionForm($workflowItem, $transition);

        $saved = false;
        if ($this->getRequest()->isMethod('POST')) {
            $transitionForm->submit($this->getRequest());

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
            array(
                'transition' => $transition,
                'saved' => $saved,
                'workflowItem' => $workflowItem,
                'form' => $transitionForm->createView(),
            )
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
                array(
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                )
            )
        );
    }

    /**
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_workflow_widget_buttons")
     * @Template
     * @AclAncestor("oro_workflow")
     */
    public function buttonsAction($entityClass, $entityId)
    {
        $workflowsData = [];

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $entity = $this->getEntityReference($entityClass, $entityId);

        $workflows = $workflowManager->getApplicableWorkflows($entity);
        foreach ($workflows as $workflow) {
            $workflowsData[$workflow->getName()] = [
                'label' => $workflow->getLabel(),
                'resetAllowed' => false,
                'transitionsData' => $this->getAvailableStartTransitionsData($workflow, $entity),
            ];
        }

        $workflowItems = $workflowManager->getWorkflowItemsByEntity($entity);
        foreach ($workflowItems as $workflowItem) {
            $name = $workflowItem->getWorkflowName();

            if ($workflowManager->isResetAllowed($entity, $workflowItem)) {
                $workflowsData[$name]['resetAllowed'] = true;
                $workflowsData[$name]['workflowItem'] = $workflowItem;
                $workflowsData[$name]['transitionsData'] = [];

                continue;
            }

            $workflowsData[$name]['transitionsData'] = $this->getAvailableTransitionsDataByWorkflowItem($workflowItem);
        }

        return [
            'entity_id' => $entityId,
            'workflowsData' => $workflowsData,
        ];
    }

    /**
     * Get transitions data for view based on workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @return array
     */
    protected function getAvailableTransitionsDataByWorkflowItem(WorkflowItem $workflowItem)
    {
        $transitionsData = array();
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $transitions = $workflowManager->getTransitionsByWorkflowItem($workflowItem);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
                if ($isAllowed || !$transition->isUnavailableHidden()) {
                    $transitionsData[$transition->getName()] = array(
                        'workflow' => $workflowManager->getWorkflow($workflowItem),
                        'workflowItem' => $workflowItem,
                        'transition' => $transition,
                        'isAllowed' => $isAllowed,
                        'errors' => $errors
                    );
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
            ->isStartTransitionAvailable($workflow, $transition, $entity, array(), $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return array(
                'workflow' => $workflowManager->getWorkflow($workflow),
                'transition' => $transition,
                'isAllowed' => $isAllowed,
                'errors' => $errors
            );
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
