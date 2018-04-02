<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Helper for getting entity workflows data used by UI
 */
class WorkflowDataHelper
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param WorkflowManager               $workflowManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TranslatorInterface           $translator
     * @param UrlGeneratorInterface         $router
     */
    public function __construct(
        WorkflowManager $workflowManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        UrlGeneratorInterface $router
    ) {
        $this->workflowManager = $workflowManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Returns array with workflows data applicable for $entity
     *
     * @param object $entity
     *
     * @return array
     */
    public function getEntityWorkflowsData($entity)
    {
        $workflows = [];
        $applicableWorkflows = array_filter(
            $this->workflowManager->getApplicableWorkflows($entity),
            function (Workflow $workflow) use ($entity) {
                return $this->isWorkflowPermissionGranted('VIEW_WORKFLOW', $workflow->getName(), $entity);
            }
        );

        foreach ($applicableWorkflows as $workflow) {
            $workflows[] = $this->getWorkflowData($entity, $workflow);
        }

        return $workflows;
    }

    /**
     * Return serialized $workflow data for $entity
     *
     * @param object $entity
     * @param Workflow $workflow
     *
     * @return array
     */
    protected function getWorkflowData($entity, Workflow $workflow)
    {
        $transitionsData = [];
        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $workflow->getName());

        // start workflow
        if (null === $workflowItem) {
            foreach ($this->getAllowedStartTransitions($workflow, $entity) as $transition) {
                $transitionsData[] = array_merge(
                    $this->getTransitionData($transition),
                    $this->getStartTransitionUrls($workflow, $transition, $entity)
                );
            }

            return [
                'name' => $workflow->getName(),
                'label' => $workflow->getLabel(),
                'isStarted' => false,
                'workflowItemId' => null,
                'transitionsData' => $transitionsData,
            ];
        }

        foreach ($this->getAllowedTransitions($workflow, $workflowItem) as $transition) {
            $transitionsData[] = array_merge(
                $this->getTransitionData($transition),
                $this->getTransitionUrls($transition, $workflowItem)
            );
        }

        return [
            'name' => $workflow->getName(),
            'label' => $workflow->getLabel(),
            'isStarted' => true,
            'workflowItemId' => $workflowItem->getId(),
            'transitionsData' => $transitionsData,
        ];
    }

    /**
     * @param Transition $transition
     *
     * @return array
     */
    protected function getTransitionData(Transition $transition)
    {
        $messageTranslation = $this->translator->trans($transition->getMessage(), [], 'workflows');

        // Do not show untranslated messages (i.e. user left the message input empty)
        if (!$transition->getMessage() || $messageTranslation === $transition->getMessage()) {
            $messageTranslation = '';
        }

        return [
            'name' => $transition->getName(),
            'label' => $this->translator->trans($transition->getLabel(), [], 'workflows'),
            'isStart' => $transition->isStart(),
            'hasForm' => $transition->hasForm(),
            'displayType' => $transition->getDisplayType(),
            'message' => $messageTranslation,
            'frontendOptions' => $transition->getFrontendOptions(),
        ];
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return array
     */
    protected function getTransitionUrls(Transition $transition, WorkflowItem $workflowItem)
    {
        $parameters = [
            'transitionName' => $transition->getName(),
            'workflowItemId' => $workflowItem->getId(),
        ];

        $urls = [
            'transitionUrl' => $this->router->generate(
                'oro_api_workflow_transit',
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];

        if ($transition->hasForm()) {
            $urls['dialogUrl'] = $this->router->generate(
                'oro_workflow_widget_transition_form',
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $urls;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     *
     * @return array
     */
    protected function getStartTransitionUrls(Workflow $workflow, Transition $transition, $entity)
    {
        $parameters = [
            'workflowName' => $workflow->getName(),
            'transitionName' => $transition->getName(),
            'entityId' => $entity->getId(),
        ];

        $urls = [
            'transitionUrl' => $this->router->generate(
                'oro_api_workflow_start',
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];

        if ($transition->hasForm()) {
            $urls['dialogUrl'] = $this->router->generate(
                'oro_workflow_widget_start_transition_form',
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $urls;
    }

    /**
     * Get transitions data for view based on workflow item.
     *
     * @param Workflow $workflow
     * @param WorkflowItem $workflowItem
     *
     * @return Transition[]
     */
    protected function getAllowedTransitions(Workflow $workflow, WorkflowItem $workflowItem)
    {
        $transitions = [];
        $availableTransitions = $workflow->getTransitionsByWorkflowItem($workflowItem);

        foreach ($availableTransitions as $transition) {
            if ($this->isTransitionAllowed($workflow, $transition, $workflowItem)) {
                $transitions[$transition->getName()] = $transition;
            }
        }

        return $transitions;
    }

    /**
     * Get array of start transitions available for workflow
     *
     * @param Workflow $workflow
     * @param object $entity
     *
     * @return Transition[]
     */
    protected function getAllowedStartTransitions(Workflow $workflow, $entity)
    {
        $transitions = [];
        $startTransitions = $workflow->getTransitionManager()->getStartTransitions();

        foreach ($startTransitions as $startTransition) {
            if ($this->isStartTransitionAllowed($workflow, $startTransition, $entity)) {
                $transitions[$startTransition->getName()] = $startTransition;
            }
        }

        if (!empty($transitions) || !$workflow->getStepManager()->hasStartStep()) {
            return $transitions;
        }

        // extra case to show start transition
        $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();

        if (null !== $defaultStartTransition) {
            $transitions[$defaultStartTransition->getName()] = $defaultStartTransition;
        }

        return $transitions;
    }

    /**
     * Check if transition is start and allowed
     *
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     *
     * @return bool
     */
    protected function isStartTransitionAllowed(Workflow $workflow, Transition $transition, $entity)
    {
        $errors = new ArrayCollection();

        return $workflow->isStartTransitionAvailable($transition->getName(), $entity, [], $errors)
               && !$transition->isHidden();
    }

    /**
     * Check if transition is available for WorkflowItem and allowed
     *
     * @param Workflow $workflow
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return bool
     */
    protected function isTransitionAllowed(Workflow $workflow, Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();

        return $workflow->isTransitionAvailable($workflowItem, $transition->getName(), $errors)
               && !$transition->isHidden();
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
        $domainObject = new DomainObjectWrapper($entity, new ObjectIdentity('workflow', $workflowName));

        return $this->authorizationChecker->isGranted($permission, $domainObject);
    }
}
