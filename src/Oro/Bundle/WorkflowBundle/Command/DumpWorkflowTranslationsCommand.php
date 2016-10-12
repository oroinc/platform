<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class DumpWorkflowTranslationsCommand extends ContainerAwareCommand
{
    const TRANSLATION_DOMAIN = 'workflows';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:workflow:translations:dump')
            ->setDescription('Dump translations')
            ->addArgument(
                'workflow',
                InputArgument::REQUIRED,
                'Workflow name whose translations should to be dumped'
            )
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL,
                'Locale whose translations should to be dumped',
                Translation::DEFAULT_LOCALE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument('locale');
        $workflowName = $input->getArgument('workflow');

        /* @var $workflowManager WorkflowManager */
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        $keys = $this->collectKeys($workflowManager->getWorkflow($workflowName));
        $translations = $this->processkeys($this->getContainer()->get('translator.default'), $keys, $locale);

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), 10));
    }

    /**
     * @param Workflow $workflow
     * @return array
     */
    protected function collectKeys(Workflow $workflow)
    {
        $config = $workflow->getDefinition()->getConfiguration();

        $keys = [
            $workflow->getLabel(),
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
     * @return array
     */
    protected function processKeys(Translator $translator, array $keys, $locale)
    {
        $translations = [];
        foreach ($keys as $key) {
            if ($translator->hasTrans($key, self::TRANSLATION_DOMAIN, $locale)) {
                $translation = $translator->trans($key, [], self::TRANSLATION_DOMAIN, $locale);
            } elseif ($translator->hasTrans($key, self::TRANSLATION_DOMAIN, Translation::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], self::TRANSLATION_DOMAIN, Translation::DEFAULT_LOCALE);
            } else {
                $translation = '';
            }
            $translations[$key] = $translation;
        }

        return $translations;
    }
}
