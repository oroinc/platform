<?php

namespace Oro\Bundle\WorkflowBundle\Layout\DataProvider;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class StartTransitionFormProvider extends TransitionFormProvider
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ButtonSearchContextProvider */
    protected $buttonSearchContextProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param ButtonSearchContextProvider $buttonSearchContext
     */
    public function setButtonSearchContextProvider(ButtonSearchContextProvider $buttonSearchContext)
    {
        $this->buttonSearchContextProvider = $buttonSearchContext;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $transitionName
     * @param string $workflowName
     *
     * @return FormInterface
     *
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function getTransitionForm($transitionName, $workflowName)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);

        $entityId = $this->requestStack->getCurrentRequest()->get('entityId', 0);
        $entityClass = $workflow->getDefinition()->getRelatedEntity();
        $dataArray = [];

        if (!$transition->isEmptyInitOptions()) {
            $contextAttribute = $transition->getInitContextAttribute();
            $dataArray[$contextAttribute] = $this->buttonSearchContextProvider
                ->getButtonSearchContext();
            $entityId = null;
        }
        $entity = $this->getOrCreateEntityReference($entityClass, $entityId);
        $workflowItem = $workflow->createWorkflowItem($entity, $dataArray);

        return parent::getTransitionForm($transitionName, $workflowItem);
    }

    /**
     * @param string $transitionName
     * @param string $workflowName
     *
     * @return FormView
     *
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    public function getTransitionFormView($transitionName, $workflowName)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->extractTransition($transitionName);

        $entityId = $this->requestStack->getCurrentRequest()->get('entityId', 0);
        $entityClass = $workflow->getDefinition()->getRelatedEntity();
        $dataArray = [];

        if (!$transition->isEmptyInitOptions()) {
            $contextAttribute = $transition->getInitContextAttribute();
            $dataArray[$contextAttribute] = $this->buttonSearchContextProvider
                ->getButtonSearchContext();
            $entityId = null;
        }
        $entity = $this->getOrCreateEntityReference($entityClass, $entityId);
        $workflowItem = $workflow->createWorkflowItem($entity, $dataArray);

        return parent::getTransitionFormView($transitionName, $workflowItem);
    }

    /**
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     *
     * @throws NotManageableEntityException
     *
     * @return mixed
     */
    protected function getOrCreateEntityReference($entityClass, $entityId = null)
    {
        if ($entityId) {
            $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
        } else {
            $entity = $this->doctrineHelper->createEntityInstance($entityClass);
        }

        return $entity;
    }
}
