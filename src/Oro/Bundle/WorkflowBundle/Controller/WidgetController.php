<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

/**
 * @Route("/workflowwidget")
 */
class WidgetController extends AbstractWidgetController
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
     * @Route("/steps/{entityClass}/{entityId}", name="oro_workflow_widget_steps")
     * @Template
     * @AclAncestor("oro_workflow")
     *
     * @param string $entityClass
     * @param int $entityId
     * @return array
     */
    public function stepsAction($entityClass, $entityId)
    {
        $entity = $this->getEntityReference($entityClass, $entityId);

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');

        $stepsData = [];

        $workflowItems = $workflowManager->getWorkflowItemsByEntity($entity);
        foreach ($workflowItems as $workflowItem) {
            $name = $workflowItem->getWorkflowName();

            $workflow = $workflowManager->getWorkflow($name);
            if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
                $steps = $workflow->getStepManager()->getOrderedSteps();
            } else {
                $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
            }

            $steps = $steps->map(function (Step $step) {
                return [
                    'name' => $step->getName(),
                    'label' => $step->getLabel()
                ];
            });

            $stepsData[$name] = [
                'workflow' => $workflow->getLabel(),
                'steps' => $steps->toArray(),
                'currentStep' => [
                    'name' => $workflowItem->getCurrentStep()->getName(),
                ],
            ];
        }

        return [
            'stepsData' => $stepsData,
        ];
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
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_workflow_widget_buttons")
     * @Template
     * @AclAncestor("oro_workflow")
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

            $workflowsData[$name]['transitionsData'] = $this->getAvailableTransitionsDataByWorkflowItem($workflowItem);
        }

        return [
            'entity_id' => $entityId,
            'workflowsData' => $workflowsData,
        ];
    }
}
