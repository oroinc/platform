<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

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
use Oro\Bundle\WorkflowBundle\Model\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\WorkflowBundle\Exception\NotManageableEntityException;

class WidgetController extends Controller
{
    /**
     * @Route("/steps/{workflowItemId}", name="oro_workflow_widget_steps")
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @Template
     * @AclAncestor("oro_workflow")
     */
    public function stepsAction(WorkflowItem $workflowItem)
    {
        $this->get('oro_workflow.http.workflow_item_validator')->validate($workflowItem);

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);
        $currentStep = $workflow->getStepManager()->getStep($workflowItem->getCurrentStepName());
        if (!$currentStep) {
            throw new BadRequestHttpException(sprintf('There is no step "%s"', $workflowItem->getCurrentStepName()));
        }

        return array(
            'steps' => $workflow->getPassedStepsByWorkflowItem($workflowItem),
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
     */
    public function startTransitionFormAction($transitionName, $workflowName)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);

        $initData = array();
        $entityClass = $this->getRequest()->get('entityClass');
        $entityId = $this->getRequest()->get('entityId');
        if ($entityClass && $entityId) {
            $entity = $this->getEntityReference($entityClass, $entityId);
            $initData = $workflowManager->getWorkflowData($entity, $initData);
        }

        $workflowItem = $workflow->createWorkflowItem($initData);
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
                $data = $serializer->serialize($workflowItem->getData(), 'json');

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
        $this->get('oro_workflow.http.workflow_item_validator')->validate($workflowItem);

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
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_workflow_widget_buttons_entity")
     * @Template
     * @AclAncestor("oro_workflow")
     */
    public function buttonsAction($entityClass, $entityId)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');

        $entity = $this->getEntityReference($entityClass, $entityId);
        $workflowName = $this->getRequest()->get('workflowName');

        $transitionsData = array();

        $existingWorkflowItems = $workflowManager->getWorkflowItemsByEntity($entity, $workflowName);
        $newWorkflows = $workflowManager->getApplicableWorkflows($entity, $existingWorkflowItems, $workflowName);

        /** @var Workflow $workflow */
        foreach ($newWorkflows as $workflow) {
            $transitionsData += $this->getAvailableStartTransitionsData($workflow, $entity);
        }

        /** @var WorkflowItem $workflowItem */
        foreach ($existingWorkflowItems as $workflowItem) {
            $transitionsData += $this->getAvailableTransitionsDataByWorkflowItem($workflowItem);
        }

        return array(
            'entity_class' => $entityClass,
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
        $transitions = $workflowManager->getStartTransitions($workflow, $entity);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $workflowManager->isStartTransitionAvailable($workflow, $transition, $entity, $errors);
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
        $doctrineHelper = $this->get('oro_workflow.doctrine_helper');
        try {
            $entity = $doctrineHelper->getEntityReference($entityClass, $entityId);
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
