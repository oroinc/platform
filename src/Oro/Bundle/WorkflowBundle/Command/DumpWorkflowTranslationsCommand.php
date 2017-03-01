<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

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

        $keys = $this->collectKeys($workflowManager->getWorkflow($workflowName)->getDefinition());
        $translations = $this->processKeys($this->getContainer()->get('translator.default'), $keys, $locale);

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return array
     */
    protected function collectKeys(WorkflowDefinition $definition)
    {
        $config = $definition->getConfiguration();

        $keys = [
            $definition->getLabel(),
        ];

        foreach ($config[WorkflowConfiguration::NODE_STEPS] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config[WorkflowConfiguration::NODE_ATTRIBUTES] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config[WorkflowConfiguration::NODE_TRANSITIONS] as $item) {
            $keys[] = $item['label'];
            $keys[] = $item['message'];
        }

        if (isset($config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS])) {
            $variableDefinitions = $config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
            foreach ($variableDefinitions[WorkflowConfiguration::NODE_VARIABLES] as $item) {
                $keys[] = $item['label'];
                if (isset($item['options']['form_options']['tooltip'])) {
                    $keys[] = $item['options']['form_options']['tooltip'];
                }
            }
        }

        return $keys;
    }

    /**
     * @param Translator $translator
     * @param array $keys
     * @param string|null $locale
     *
     * @return array
     */
    protected function processKeys(Translator $translator, array $keys, $locale)
    {
        $translations = [];
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        foreach ($keys as $key) {
            if ($translator->hasTrans($key, $domain, $locale)) {
                $translation = $translator->trans($key, [], $domain, $locale);
            } elseif ($translator->hasTrans($key, $domain, Translator::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], $domain, Translator::DEFAULT_LOCALE);
            } else {
                $translation = '';
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
