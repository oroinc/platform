<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowVariablesHandler;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDeactivationHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Oro\Bundle\WorkflowBundle\Translation\TranslationsDatagridLinksProvider;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Workflow definition controller
 */
#[Route(path: '/workflowdefinition')]
class WorkflowDefinitionController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(name: 'oro_workflow_definition_index')]
    #[Template('@OroWorkflow/WorkflowDefinition/index.html.twig')]
    #[Acl(id: 'oro_workflow_definition_view', type: 'entity', class: WorkflowDefinition::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'entity_class' => WorkflowDefinition::class,
        ];
    }

    /**
     *
     * @return array
     */
    #[Route(path: '/create', name: 'oro_workflow_definition_create')]
    #[Template('@OroWorkflow/WorkflowDefinition/update.html.twig')]
    #[Acl(
        id: 'oro_workflow_definition_create',
        type: 'entity',
        class: WorkflowDefinition::class,
        permission: 'CREATE'
    )]
    public function createAction()
    {
        return $this->updateAction(new WorkflowDefinition());
    }

    /**
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     * @throws AccessDeniedException
     */
    #[Route(path: '/update/{name}', name: 'oro_workflow_definition_update')]
    #[Template('@OroWorkflow/WorkflowDefinition/update.html.twig')]
    #[Acl(id: 'oro_workflow_definition_update', type: 'entity', class: WorkflowDefinition::class, permission: 'EDIT')]
    public function updateAction(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem() || !$this->isEditable($workflowDefinition)) {
            throw new AccessDeniedException('System workflow definitions are not editable');
        }
        $translateLinks = $this->container->get(TranslationsDatagridLinksProvider::class)
            ->getWorkflowTranslateLinks($workflowDefinition);
        $this->container->get(TranslationProcessor::class)
            ->translateWorkflowDefinitionFields($workflowDefinition, true);

        $form = $this->container->get(FormFactoryInterface::class)->createNamed(
            'oro_workflow_definition_form',
            WorkflowDefinitionType::class,
            null
        );

        $form->setData($workflowDefinition);

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'delete_allowed' => true,
            'translateLinks' => $translateLinks,
            'availableDestinations' => DestinationPageResolver::AVAILABLE_DESTINATIONS,
        ];
    }

    /**
     *
     * @param Request $request
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     * @throws AccessDeniedException
     */
    #[Route(path: '/configure/{name}', name: 'oro_workflow_definition_configure')]
    #[Template('@OroWorkflow/WorkflowDefinition/configure.html.twig')]
    #[Acl(
        id: 'oro_workflow_definition_configure',
        type: 'entity',
        class: WorkflowDefinition::class,
        permission: 'CONFIGURE'
    )]
    public function configureAction(Request $request, WorkflowDefinition $workflowDefinition)
    {
        $workflow = $this->container->get(WorkflowManagerRegistry::class)
            ->getManager('system')
            ->getWorkflow($workflowDefinition->getName());

        if (!count($workflow->getVariables())) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(WorkflowVariablesType::class, null, ['workflow' => $workflow]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->container->get(WorkflowVariablesHandler::class)
                    ->updateWorkflowVariables($workflowDefinition, $form->getData());

                $this->addFlash(
                    'success',
                    $this->container->get(TranslatorInterface::class)
                        ->trans('oro.workflow.variable.save.success_message')
                );

                return $this->container->get(Router::class)->redirect($workflowDefinition);
            }
        }

        $translateLinksProvider = $this->container->get(TranslationsDatagridLinksProvider::class);

        return [
            'form' => $form->createView(),
            'entity' => $workflowDefinition,
            'translateLinks' => $translateLinksProvider->getWorkflowTranslateLinks($workflowDefinition),
        ];
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    #[Route(path: '/view/{name}', name: 'oro_workflow_definition_view')]
    #[Template('@OroWorkflow/WorkflowDefinition/view.html.twig')]
    #[AclAncestor('oro_workflow_definition_view')]
    public function viewAction(WorkflowDefinition $workflowDefinition)
    {
        $translateLinks = $this->container->get(TranslationsDatagridLinksProvider::class)
            ->getWorkflowTranslateLinks($workflowDefinition);
        $this->container->get(TranslationProcessor::class)
            ->translateWorkflowDefinitionFields($workflowDefinition, true);
        $workflow = $this->container->get(WorkflowManagerRegistry::class)
            ->getManager('system')
            ->getWorkflow($workflowDefinition->getName());

        return [
            'entity' => $workflowDefinition,
            'translateLinks' => $translateLinks,
            'variables' => $workflow->getVariables(true),
            'edit_allowed' => $this->isEditable($workflowDefinition)
        ];
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     */
    protected function isEditable(WorkflowDefinition $workflowDefinition)
    {
        return $this->container->get(ConfigurationChecker::class)->isClean($workflowDefinition->getConfiguration());
    }

    /**
     * Activate WorkflowDefinition form
     *
     *
     * @param Request $request
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    #[Route(path: '/activate-form/{name}', name: 'oro_workflow_definition_activate_from_widget')]
    #[Template('@OroWorkflow/WorkflowDefinition/widget/activateForm.html.twig')]
    #[AclAncestor('oro_workflow_definition_update')]
    public function activateFormAction(Request $request, WorkflowDefinition $workflowDefinition)
    {
        $form = $this->createForm(WorkflowReplacementType::class, null, ['workflow' => $workflowDefinition]);
        $response = ['form' => $form->createView()];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workflowManager = $this->container->get(WorkflowManagerRegistry::class)->getManager();
            $helper = $this->container->get(WorkflowDeactivationHelper::class);
            $data = $form->getData();

            try {
                $workflows = array_merge(
                    $data['workflowsToDeactivation'],
                    $helper->getWorkflowsToDeactivation($workflowDefinition)
                        ->map(
                            function (Workflow $workflow) {
                                return $workflow->getName();
                            }
                        )->getValues()
                );

                $response['deactivated'] = $this->deactivateWorkflows($workflowManager, $workflows);

                $workflowManager->activateWorkflow($workflowDefinition->getName());

                $response['savedId'] = $workflowDefinition->getName();
            } catch (\RuntimeException $e) {
                $response['error'] = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * @param WorkflowManager $workflowManager
     * @param array $workflowNames
     * @return array
     */
    private function deactivateWorkflows(WorkflowManager $workflowManager, array $workflowNames)
    {
        $deactivated = [];
        $translationHelper = $this->container->get(WorkflowTranslationHelper::class);

        foreach ($workflowNames as $workflowName) {
            if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                $workflow = $workflowManager->getWorkflow($workflowName);

                $workflowManager->resetWorkflowData($workflow->getName());
                $workflowManager->deactivateWorkflow($workflow->getName());

                $deactivated[] = $translationHelper->findWorkflowTranslation(
                    $workflow->getLabel(),
                    $workflow->getName()
                );
            }
        }

        return $deactivated;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                FormFactoryInterface::class,
                Router::class,
                WorkflowManagerRegistry::class,
                WorkflowVariablesHandler::class,
                ConfigurationChecker::class,
                WorkflowDeactivationHelper::class,
                WorkflowTranslationHelper::class,
                TranslationsDatagridLinksProvider::class,
                TranslationProcessor::class,
            ]
        );
    }
}
