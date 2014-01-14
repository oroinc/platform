<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;

class OroTranslationDumpCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:dump')
            ->setDescription('Dumps oro js-translations')
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'List of locales, whose translations should to be dumped'
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag to dump js-translation resources with debug mode',
                false
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locales = $input->getArgument('locale');
        if (empty($locales)) {
            $locales[] = $this->getContainer()->getParameter('kernel.default_locale');
        }

        $dumper = $this->getContainer()->get('oro_translation.js_dumper');
        $dumper->setLogger(new OutputLogger($output));
        $dumper->dumpTranslations($locales);
    }
}
