<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserRoleViewForm;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDeactivationHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ManagerRegistry $managerRegistry;

    private TranslatorInterface $translator;

    private WorkflowTranslationHelper $workflowTranslationHelper;

    private WorkflowRegistry $workflowRegistry;

    private DoctrineHelper $doctrineHelper;

    private WorkflowDeactivationHelper $workflowDeactivationHelper;

    private WorkflowManagerRegistry $workflowManagerRegistry;

    private CommandRunnerInterface $commandRunner;

    private WorkflowAwareCache $workflowAwareCache;

    private TranslationDomainProvider $translationDomainProvider;

    private JsTranslationDumper $jsTranslationDumper;

    public function __construct(
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
        WorkflowTranslationHelper $workflowTranslationHelper,
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        WorkflowDeactivationHelper $workflowDeactivationHelper,
        WorkflowManagerRegistry $workflowManagerRegistry,
        CommandRunnerInterface $commandRunner,
        WorkflowAwareCache $workflowAwareCache,
        TranslationDomainProvider $translationDomainProvider,
        JsTranslationDumper $jsTranslationDumper
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
        $this->workflowTranslationHelper = $workflowTranslationHelper;
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->workflowDeactivationHelper = $workflowDeactivationHelper;
        $this->workflowManagerRegistry = $workflowManagerRegistry;
        $this->commandRunner = $commandRunner;
        $this->workflowAwareCache = $workflowAwareCache;
        $this->translationDomainProvider = $translationDomainProvider;
        $this->jsTranslationDumper = $jsTranslationDumper;
    }

    /**
     * Example: And I append grid "test-grid" for active workflow "My Workflow Title"
     *
     * @Then /^(?:|I )append grid "(?P<gridName>[^"]*)" for active workflow "(?P<workflowTitle>[^"]*)"$/
     *
     * @param string $gridName
     * @param string $workflowTitle
     */
    public function iAppendGridForActiveWorkflow($gridName, $workflowTitle)
    {
        $workflow = $this->getWorkflowByTitle($workflowTitle);
        self::assertNotNull($workflow, sprintf('There is no workflow with title "%s"', $workflowTitle));

        $configuration = $workflow->getDefinition()->getConfiguration();
        $configuration[WorkflowDefinition::CONFIG_DATAGRIDS][] =  $gridName;
        $workflow->getDefinition()->setConfiguration($configuration);

        $this->managerRegistry->getManagerForClass(WorkflowDefinition::class)->flush();
    }

    /**
     * Example: And I allow workflow "My Workflow Title" for "default" application
     *
     * @Then /^(?:|I )allow workflow "(?P<workflowTitle>[^"]*)" for "(?P<group>[^"]*)" application$/
     *
     * @param string $workflowTitle
     * @param string $group
     */
    public function iAllowWorkflowForApplication($workflowTitle, $group)
    {
        $workflow = $this->getWorkflowByTitle($workflowTitle);
        self::assertNotNull($workflow, sprintf('There is no workflow with title "%s"', $workflowTitle));

        $applications = $workflow->getDefinition()->getApplications();
        $applications[] = $group;

        $workflow->getDefinition()->setApplications($applications);

        $this->managerRegistry->getManagerForClass(WorkflowDefinition::class)->flush();
    }

    /**
     * @param string $title
     * @return Workflow|null
     */
    protected function getWorkflowByTitle($title)
    {
        $workflows = $this->workflowRegistry->getActiveWorkflows()->filter(
            function (Workflow $workflow) use ($title) {
                return $title === $this->workflowTranslationHelper->findTranslation($workflow->getLabel());
            }
        );

        return $workflows->isEmpty() ? null : $workflows->first();
    }

    private function getWorkflowDefinitionByTitle(string $title): ?WorkflowDefinition
    {
        $workflowDefinitionRepository = $this->doctrineHelper->getEntityRepositoryForClass(WorkflowDefinition::class);

        $workflowDefinitions = array_filter(
            $workflowDefinitionRepository->findAll(),
            function (WorkflowDefinition $workflow) use ($title) {
                return $title === $this->workflowTranslationHelper->findTranslation($workflow->getLabel());
            }
        );

        return $workflowDefinitions ? reset($workflowDefinitions) : null;
    }

    /**
     * Asserts that provided workflow permissions allowed on role view page
     *
     * Example: Then the role has following active workflow permissions:
     *            | Test Workflow | View Workflow:Global | Perform transitions:Global |
     *
     * @Then /^the role has following active workflow permissions:$/
     */
    public function iSeeFollowingWorkflowPermissions(TableNode $table)
    {
        /** @var UserRoleViewForm $userRoleForm */
        $userRoleForm = $this->elementFactory->createElement('User Role View Workflow Permissions');
        $permissionNames = $table->getColumn(0);
        $permissionsArray = $userRoleForm->getPermissionsByNames($permissionNames);
        foreach ($table->getRows() as $row) {
            $workflowName = array_shift($row);

            foreach ($row as $cell) {
                [$role, $value] = explode(':', $cell);
                self::assertNotEmpty($permissionsArray[$workflowName][$role]);
                $expected = $permissionsArray[$workflowName][$role];
                self::assertEquals(
                    $expected,
                    $value,
                    "Failed asserting that workflow permission $expected equals $value for $workflowName"
                );
            }
        }
    }

    /**
     * @Given /^complete workflow fixture loading$/
     */
    public function completeWorkflowFixtureLoading()
    {
        $this->workflowAwareCache->invalidateActiveRelated();

        $this->translationDomainProvider->clearCache();

        $this->translator->rebuildCache();

        $this->jsTranslationDumper->dumpTranslations();
    }

    /**
     * @Given /^(?:I )?activate "(?P<workflowTitle>[^"]*)" workflow$/
     */
    public function activateWorkflow(string $workflowTitle): void
    {
        $workflowDefinition = $this->getWorkflowDefinitionByTitle($workflowTitle);

        self::assertNotNull($workflowDefinition, sprintf('Workflow %s was not found', $workflowTitle));

        try {
            $workflowsToDeactivate = $this->workflowDeactivationHelper->getWorkflowsToDeactivation($workflowDefinition)
                ->map(
                    function (Workflow $workflow) {
                        return $workflow->getName();
                    }
                )->getValues();

            $this->deactivateWorkflows($workflowsToDeactivate);
        } catch (WorkflowException $e) {
            $workflowDefinition = null;
        }

        self::assertNotEmpty($workflowDefinition, sprintf('Workflow %s could not be activated', $workflowTitle));

        $this->workflowManagerRegistry->getManager()->activateWorkflow($workflowDefinition->getName());
    }

    /**
     * @When scheduled cron processes are executed
     */
    public function processScheduledCronTriggers()
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(Schedule::class);

        $schedules = $repository->findBy(['command' => HandleProcessTriggerCommand::getDefaultName()]);

        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $this->commandRunner->run($schedule->getCommand(), $this->resolveCommandOptions($schedule->getArguments()));
        }
    }

    /**
     * @When price lists scheduled cron processes are executed
     */
    public function processPriceListsScheduledCronTriggers()
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(Schedule::class);

        $schedules = $repository->findBy(['command' => 'oro:cron:price-lists:schedule']);

        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $this->commandRunner->run($schedule->getCommand(), $this->resolveCommandOptions($schedule->getArguments()));
        }
    }

    /**
     * @param array $commandOptions
     * @return array
     */
    private function resolveCommandOptions(array $commandOptions)
    {
        $options = [];
        foreach ($commandOptions as $key => $option) {
            $params = explode('=', $option, 2);
            if (is_array($params) && count($params) === 2) {
                $options[$params[0]] = $params[1];
            } else {
                $options[$key] = $option;
            }
        }

        return $options;
    }

    /**
     * @throws WorkflowException
     */
    private function deactivateWorkflows(array $workflowNames): void
    {
        $workflowManager = $this->workflowManagerRegistry->getManager();

        foreach ($workflowNames as $workflowName) {
            if ($workflowName && $workflowManager->isActiveWorkflow($workflowName)) {
                $workflow = $workflowManager->getWorkflow($workflowName);

                $workflowManager->resetWorkflowData($workflow->getName());
                $workflowManager->deactivateWorkflow($workflow->getName());
            }
        }
    }
}
