<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\TransitActionProcessor;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDataProvider;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Provides data for building workflow widget
 */
#[Route(path: '/workflowwidget')]
class WidgetController extends AbstractController
{
    /**
     *
     * @param string $entityClass
     * @param int $entityId
     *
     * @return array
     */
    #[Route(path: '/entity-workflows/{entityClass}/{entityId}', name: 'oro_workflow_widget_entity_workflows')]
    #[Template]
    public function entityWorkflowsAction($entityClass, $entityId)
    {
        $entity = $this->container->get(TransitionWidgetHelper::class)
            ->getOrCreateEntityReference($entityClass, $entityId);
        if (!$entity) {
            throw $this->createNotFoundException(
                sprintf('Entity \'%s\' with id \'%d\' not found', $entityClass, $entityId)
            );
        }

        $workflowManager = $this->container->get(WorkflowManagerRegistry::class)->getManager();
        $applicableWorkflows = array_filter(
            $workflowManager->getApplicableWorkflows($entity),
            function (Workflow $workflow) use ($entity) {
                return $this->isWorkflowPermissionGranted('VIEW_WORKFLOW', $workflow->getName(), $entity);
            }
        );

        /* @var TransitionTranslationHelper $translationHelper */
        $translationHelper = $this->container->get(TransitionTranslationHelper::class);

        return [
            'entityId' => $entityId,
            'workflows' => array_map(
                function (Workflow $workflow) use ($entity, $translationHelper) {
                    // extra case to show start transition (step name and disabled button)
                    // even if transitions performing is forbidden with ACL
                    $showDisabled = !$this->isWorkflowPermissionGranted(
                        'PERFORM_TRANSITIONS',
                        $workflow->getName(),
                        $entity
                    );

                    $workflowData = $this->container->get(WorkflowDataProvider::class)
                        ->getWorkflowData($entity, $workflow, $showDisabled);

                    foreach ($workflowData['transitionsData'] as $transitionData) {
                        $translationHelper->processTransitionTranslations($transitionData['transition']);
                    }

                    return $workflowData;
                },
                $applicableWorkflows
            ),
            'originalUrl' => $this->container->get(OriginalUrlProvider::class)->getOriginalUrl(),
        ];
    }

    /**
     *
     * @param string $transitionName
     * @param string $workflowName
     * @param Request $request
     * @return Response
     */
    #[Route(
        path: '/transition/create/attributes/{workflowName}/{transitionName}',
        name: 'oro_workflow_widget_start_transition_form'
    )]
    public function startTransitionFormAction($transitionName, $workflowName, Request $request)
    {
        $processor = $this->container->get(TransitActionProcessor::class);

        $context = $this->createProcessorContext($processor, $request, $transitionName);
        $context->setWorkflowName($workflowName);

        $processor->process($context);

        return $context->getResult();
    }

    /**
     *
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     * @param Request $request
     *
     * @return Response
     */
    #[Route(
        path: '/transition/edit/attributes/{workflowItemId}/{transitionName}',
        name: 'oro_workflow_widget_transition_form'
    )]
    #[ParamConverter('workflowItem', options: ['id' => 'workflowItemId'])]
    public function transitionFormAction($transitionName, WorkflowItem $workflowItem, Request $request)
    {
        $processor = $this->container->get(TransitActionProcessor::class);

        $context = $this->createProcessorContext($processor, $request, $transitionName);
        $context->setWorkflowItem($workflowItem);

        $processor->process($context);

        return $context->getResult();
    }

    /**
     * @param TransitActionProcessor $processor
     * @param Request $request
     * @param string $transitionName
     *
     * @return TransitionContext
     */
    private function createProcessorContext(TransitActionProcessor $processor, Request $request, $transitionName)
    {
        /** @var TransitionContext $context */
        $context = $processor->createContext();
        $context->setTransitionName($transitionName);
        $context->setRequest($request);
        $context->setResultType(new TemplateResultType());

        return $context;
    }

    /**
     *
     * @param string $entityClass
     * @param int $entityId
     *
     * @return array
     */
    #[Route(path: '/buttons/{entityClass}/{entityId}', name: 'oro_workflow_widget_buttons')]
    #[Template]
    public function buttonsAction($entityClass, $entityId)
    {
        $workflowsData = [];

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->container->get(WorkflowManagerRegistry::class)->getManager('default');
        $transitionDataProvider = $this->container->get(TransitionDataProvider::class);
        $entity = $this->container->get(TransitionWidgetHelper::class)
            ->getOrCreateEntityReference($entityClass, $entityId);

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
        return $this->isGranted(
            $permission,
            new DomainObjectWrapper($entity, new ObjectIdentity('workflow', $workflowName))
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TransitionWidgetHelper::class,
                WorkflowManagerRegistry::class,
                TransitionTranslationHelper::class,
                WorkflowDataProvider::class,
                OriginalUrlProvider::class,
                TransitActionProcessor::class,
                TransitionDataProvider::class,
            ]
        );
    }
}
