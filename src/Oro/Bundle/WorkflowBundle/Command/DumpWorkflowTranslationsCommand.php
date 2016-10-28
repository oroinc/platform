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

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class DumpWorkflowTranslationsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:translations:dump';
    const INLINE_LEVEL = 10;
    const TRANSLATION_DOMAIN = 'workflows';

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

        foreach ($config['steps'] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config['attributes'] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config['transitions'] as $item) {
            $keys[] = $item['label'];
            $keys[] = $item['message'];
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
        foreach ($keys as $key) {
            if ($translator->hasTrans($key, self::TRANSLATION_DOMAIN, $locale)) {
                $translation = $translator->trans($key, [], self::TRANSLATION_DOMAIN, $locale);
            } elseif ($translator->hasTrans($key, self::TRANSLATION_DOMAIN, Translator::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], self::TRANSLATION_DOMAIN, Translator::DEFAULT_LOCALE);
            } else {
                $translation = '';
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
