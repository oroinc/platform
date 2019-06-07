<?php

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
 * Dump workflow translations
 */
class DumpWorkflowTranslationsCommand extends Command
{
    public const INLINE_LEVEL = 10;

    /** @var string */
    protected static $defaultName = 'oro:workflow:translations:dump';

    /** @var WorkflowManager */
    private $workflowManager;

    /** @var WorkflowTranslationHelper */
    private $workflowTranslationHelper;

    /**
     * @param WorkflowManager $workflowManager
     * @param WorkflowTranslationHelper $workflowTranslationHelper
     */
    public function __construct(WorkflowManager $workflowManager, WorkflowTranslationHelper $workflowTranslationHelper)
    {
        parent::__construct();

        $this->workflowManager = $workflowManager;
        $this->workflowTranslationHelper = $workflowTranslationHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Dump translations')
            ->addArgument(
                'workflow',
                InputArgument::REQUIRED,
                'Workflow name whose translations should to be dumped'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_OPTIONAL,
                'Locale whose translations should to be dumped',
                Translator::DEFAULT_LOCALE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getOption('locale');
        $workflowName = $input->getArgument('workflow');

        $keys = $this->workflowTranslationHelper->generateDefinitionTranslationKeys(
            $this->workflowManager->getWorkflow($workflowName)->getDefinition()
        );
        $translations = $this->workflowTranslationHelper->generateDefinitionTranslations($keys, $locale);

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));
    }
}
