<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\ORM\EntityManager;

use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

class TransitionWidgetHelper
{
    const DEFAULT_TRANSITION_TEMPLATE = 'OroWorkflowBundle:Widget:widget/transitionForm.html.twig';
    const DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE = 'OroWorkflowBundle:Widget:widget/transitionCustomForm.html.twig';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var WorkflowAwareSerializer */
    protected $workflowDataSerializer;

    /**
     * TransitionWidgetHelper constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     * @param FormFactoryInterface $formFactory
     * @param WorkflowAwareSerializer $workflowDataSerializer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FormFactoryInterface $formFactory,
        WorkflowAwareSerializer $workflowDataSerializer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->formFactory = $formFactory;
        $this->workflowDataSerializer = $workflowDataSerializer;
    }

    /**
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     *
     * @throws BadRequestHttpException
     * @return mixed
     */
    public function getOrCreateEntityReference($entityClass, $entityId = null)
    {
        try {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass('OroWorkflowBundle:WorkflowItem');
    }

    /**
     * Get transition form.
     *
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     *
     * @return FormInterface
     */
    public function getTransitionForm(WorkflowItem $workflowItem, Transition $transition)
    {
        $formType = $transition->getFormType();

        if ($transition->hasFormConfiguration()) {
            if (array_key_exists('form_init', $transition->getFormOptions())) {
                /** @var ActionInterface $action */
                $action = $transition->getFormOptions()['form_init'];
                $action->execute($workflowItem);
            }
            $formData = $workflowItem->getData()->get($transition->getFormDataAttribute());
            $formOptions = [];
        } else {
            $formData = $workflowItem->getData();
            $formOptions = array_merge(
                $transition->getFormOptions(),
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transition->getName()
                ]
            );
        }

        if (!$formData) {
            throw new BadRequestHttpException('Data for transition form is not defined');
        }

        return $this->formFactory->create($formType, $formData, $formOptions);
    }

    /**
     * @param Transition $transition
     *
     * @return string
     */
    public function getTransitionFormTemplate(Transition $transition)
    {
        if ($transition->hasFormConfiguration()) {
            return $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE;
        } else {
            return $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE;
        }
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param FormInterface $transitionForm
     * @param array $dataArray
     *
     * @return string
     */
    public function processWorkflowData(
        Workflow $workflow,
        Transition $transition,
        FormInterface $transitionForm,
        array $dataArray = []
    ) {

        $this->workflowDataSerializer->setWorkflowName($workflow->getName());
        if ($transition->hasFormConfiguration()) {
            $data = [$transition->getFormDataAttribute() => $transitionForm->getData()];
        } else {
            $formOptions = $transition->getFormOptions();
            $attributeNames = array_keys($formOptions['attribute_fields']);
            $data = $transitionForm->getData()->getValues($attributeNames);
        }

        return $this->workflowDataSerializer->serialize(new WorkflowData(array_merge($data, $dataArray)), 'json');
    }
}
