<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

class DumpWorkflowTranslationsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:translations:dump';
    const INLINE_LEVEL = 10;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Dump translations')
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

        /* @var $workflowManager WorkflowManager */
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        /** @var WorkflowTranslationHelper $workflowTranslationHelper */
        $workflowTranslationHelper = $this->getContainer()->get('oro_workflow.helper.translation');

        $keys = $workflowTranslationHelper->generateDefinitionTranslationKeys(
            $workflowManager->getWorkflow($workflowName)->getDefinition()
        );
        $translations = $workflowTranslationHelper->generateDefinitionTranslations($keys, $locale);

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));
    }
}
