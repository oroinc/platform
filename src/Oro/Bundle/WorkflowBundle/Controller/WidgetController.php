<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;

class WidgetController extends Controller
{
    /**
     * @Route("/steps/{entityClass}/{entityId}", name="oro_workflow_widget_steps")
     * @Template
     * @AclAncestor("oro_workflow")
     */
    public function stepsAction($entityClass, $entityId)
    {
        /** @var EntityConnector $entityConnector */
        $entityConnector = $this->get('oro_workflow.entity_connector');
        $entity = $this->getEntityReference($entityClass, $entityId);

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflowItem = $entityConnector->getWorkflowItem($entity);
        $currentStep = $entityConnector->getWorkflowStep($entity);
        $steps = array();
        if ($workflowItem) {
            $workflow = $workflowManager->getWorkflow($workflowItem);

            $workflowDefinition = $workflow->getDefinition();
            if (!$workflowDefinition->isStepsDisplayOrdered()) {
                $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
            }

            if (!$currentStep) {
                $currentStepName = $workflowItem->getCurrentStep()->getName();
                $currentStep = $workflow->getStepManager()->getStep($currentStepName);
            }
        } else {
            $workflow = $workflowManager->getApplicableWorkflow($entity);
            $workflowDefinition = $workflow->getDefinition();
            if (!$currentStep && $workflowDefinition->getStartStep()) {
                $currentStep = $workflow->getStepManager()
                    ->getStep($workflowDefinition->getStartStep()->getName());

                if (!$workflowDefinition->isStepsDisplayOrdered()) {
                    $steps = array($currentStep);
                }
            }
        }

        if ($workflowDefinition->isStepsDisplayOrdered()) {
            $steps = $workflow->getStepManager()->getOrderedSteps();
        }
        if (!$steps && $currentStep) {
            $steps[] = $currentStep;
        }

        return array(
            'steps' => $steps,
            'currentStep' => $currentStep,
        );
    }

    /**
     * @Route(
     *      "/transition/create/attributes/{transitionName}/{workflowName}",
     *      name="oro_workflow_widget_start_transition_form"
     * )
     * @Template("OroWorkflowBundle:Widget:transitionForm.html.twig")
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
                /** @var WorkflowAwareSerializer $serializer */
                $serializer = $this->get('oro_workflow.serializer.data.serializer');
                $serializer->setWorkflowName($workflow->getName());

                // Create new WorkflowData instance with all data required to start.
                // Original WorkflowData can not be used, as some attributes may be set by reference
                // So, serialized data will not contain all required data.
                $formOptions = $transition->getFormOptions();
                $attributes = array_keys($formOptions['attribute_fields']);

                $existingAttributes = $workflowItem->getData()->getValues();
                $formAttributes = $workflowItem->getData()->getValues($attributes);
                foreach ($formAttributes as $value) {
                    if (is_object($value) && $doctrineHelper->isManageableEntity($value)) {
                        $entityManager = $doctrineHelper->getEntityManager($value);
                        $unitOfWork = $entityManager->getUnitOfWork();
                        if (!$unitOfWork->isInIdentityMap($value) || $unitOfWork->isScheduledForInsert($value)) {
                            $entityManager->persist($value);
                            $entityManager->flush($value);
                        }
                    }
                }

                $data = $serializer->serialize(new WorkflowData($existingAttributes + $formAttributes), 'json');
                $saved = true;
            }
        }

        return array(
            'transition' => $transition,
            'data' => $data,
            'saved' => $saved,
            'workflowItem' => $workflowItem,
            'form' => $transitionForm->createView(),
        );
    }

    /**
     * @Route(
     *      "/transition/edit/attributes/{transitionName}/{workflowItemId}",
     *      name="oro_workflow_widget_transition_form"
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @Template("OroWorkflowBundle:Widget:transitionForm.html.twig")
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
            }
        }

        return array(
            'transition' => $transition,
            'saved' => $saved,
            'workflowItem' => $workflowItem,
            'form' => $transitionForm->createView(),
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
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $entity = $this->getEntityReference($entityClass, $entityId);

        $workflowItem = $workflowManager->getWorkflowItemByEntity($entity);
        if ($workflowItem) {
            $transitionsData = $this->getAvailableTransitionsDataByWorkflowItem($workflowItem);
        } else {
            $workflow = $workflowManager->getApplicableWorkflow($entity);
            $transitionsData = $this->getAvailableStartTransitionsData($workflow, $entity);
        }

        return array(
            'entity_id' => $entityId,
            'transitionsData' => $transitionsData
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
                    $transitionsData[] = array(
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
        $transitionsData = array();
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $transitions = $workflowManager->getStartTransitions($workflow);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $workflowManager
                    ->isStartTransitionAvailable($workflow, $transition, $entity, array(), $errors);
                if ($isAllowed || !$transition->isUnavailableHidden()) {
                    $transitionsData[] = array(
                        'workflow' => $workflowManager->getWorkflow($workflow),
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
