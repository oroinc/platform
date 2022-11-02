<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps translations for use in JavaScript.
 */
class OroTranslationDumpCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:dump';

    private JsTranslationDumper $dumper;

    public function __construct(JsTranslationDumper $dumper)
    {
        $this->dumper = $dumper;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('locale', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Locales')
            ->addOption('debug', null, InputOption::VALUE_OPTIONAL, '[Obsolete option, do not use]', false)
            ->setDescription('Dumps translations for use in JavaScript.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the translations used by JavaScript code
into the predefined public resource files.

  <info>php %command.full_name%</info>

The <info>--locale</info> option can be used to dump translations only for the specified locales:

  <info>php %command.full_name% --locale=<locale1> --locale=<locale2> --locale=<localeN></info>

HELP
            )
            ->addUsage('--locale=<locale1> --locale=<locale2> --locale=<localeN>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $locales = $input->getArgument('locale');
        if (!$locales) {
            $locales = $this->dumper->getAllLocales();
        }
        foreach ($locales as $locale) {
            $translationFile = $this->dumper->dumpTranslationFile($locale);
            $io->text('<info>[file+]</info> ' . $translationFile);
        }

        return 0;
    }
}
