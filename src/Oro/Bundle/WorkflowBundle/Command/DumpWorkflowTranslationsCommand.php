<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps workflow translations.
 */
class DumpWorkflowTranslationsCommand extends Command
{
    public const INLINE_LEVEL = 10;

    /** @var string */
    protected static $defaultName = 'oro:workflow:translations:dump';

    private WorkflowManager $workflowManager;
    private WorkflowTranslationHelper $workflowTranslationHelper;

    public function __construct(WorkflowManager $workflowManager, WorkflowTranslationHelper $workflowTranslationHelper)
    {
        parent::__construct();

        $this->workflowManager = $workflowManager;
        $this->workflowTranslationHelper = $workflowTranslationHelper;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('workflow', InputArgument::REQUIRED, 'Workflow name')
            ->addOption('parent-workflow', null, InputOption::VALUE_OPTIONAL, 'Parent workflow name')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Locale', Translator::DEFAULT_LOCALE)
            ->setDescription('Dumps workflow translations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps (prints) workflow translations
(workflow label, step labels, attribute labels, transition labels, button labels,
button title and warning messages) of a specified workflow.

  <info>php %command.full_name% <workflow></info>

The <info>--locale</info> option can be used to specify a different target locale.

  <info>php %command.full_name% --locale=<locale> <workflow></info>
  
The <info>--parent-workflow</info> option can be used to specify a parent workflow from which translations are inherited
  <info>php %command.full_name% --locale=<locale> --parent-workflow=<parent_workflow> <workflow></info>

HELP
            )
            ->addUsage('--locale=<locale> <workflow>')
            ->addUsage('--parent-workflow=<parent_workflow> <workflow>')
            ->addUsage('--parent-workflow=<parent_workflow> --locale=<locale> <workflow>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locale = $input->getOption('locale');
        $workflowName = $input->getArgument('workflow');
        $parentWorkflowName = $input->getOption('parent-workflow');

        $translations = $this->getWorkflowTranslations($workflowName, $locale);
        $parentWorkflowTranslations = $this->getParentWorkflowTranslations(
            $workflowName,
            $parentWorkflowName,
            $locale,
            $translations
        );
        $translations = array_merge($translations, $parentWorkflowTranslations);

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));

        return Command::SUCCESS;
    }

    private function getWorkflowTranslations(string $workflowName, string $locale, array $skipKeys = []): array
    {
        $keys = $this->workflowTranslationHelper->generateDefinitionTranslationKeys(
            $this->workflowManager->getWorkflow($workflowName)->getDefinition()
        );
        if ($skipKeys) {
            $keys = array_values(array_diff($keys, $skipKeys));
        }

        return $this->workflowTranslationHelper->generateDefinitionTranslations($keys, $locale);
    }

    private function getParentWorkflowTranslations(
        string $workflowName,
        ?string $parentWorkflowName,
        string $locale,
        array $existingWorkflowTranslations = []
    ): array {
        if (!$parentWorkflowName) {
            return [];
        }
        $nonEmptyTranslations = array_filter($existingWorkflowTranslations);
        $workflowExistingKeys = array_keys($nonEmptyTranslations);

        $skipKeys = array_map(function (string $key) use ($workflowName, $parentWorkflowName) {
            return str_replace(
                '.' . $workflowName . '.',
                '.' . $parentWorkflowName . '.',
                $key
            );
        }, $workflowExistingKeys);

        $parentTranslations = $this->getWorkflowTranslations($parentWorkflowName, $locale, $skipKeys);
        $translations = [];
        foreach ($parentTranslations as $key => $translation) {
            $newKey = str_replace(
                '.' . $parentWorkflowName . '.',
                '.' . $workflowName . '.',
                $key
            );
            $translations[$newKey] = $translation;
        }

        return $translations;
    }
}
