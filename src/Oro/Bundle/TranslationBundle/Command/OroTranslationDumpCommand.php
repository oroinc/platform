<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps oro js-translations
 */
class OroTranslationDumpCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:dump';

    /** @var JsTranslationDumper */
    private $dumper;

    /**
     * @param JsTranslationDumper $dumper
     */
    public function __construct(JsTranslationDumper $dumper)
    {
        $this->dumper = $dumper;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

        $this->dumper->setLogger(new OutputLogger($output));
        $this->dumper->dumpTranslations($locales);
    }
}
