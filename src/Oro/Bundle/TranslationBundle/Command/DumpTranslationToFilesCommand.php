<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Provider\Catalogue\CatalogueLoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Writer\TranslationWriter;

/**
 * Dumps the translations for the given languages in separate formats.
 */
class DumpTranslationToFilesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:dump-files';

    private TranslationWriter $translationWriter;
    private TranslationReader $translationReader;
    private string $targetPath;

    /** @var iterable|CatalogueLoaderInterface[] */
    private iterable $catalogueLoaders;

    public function __construct(
        TranslationWriter $translationWriter,
        TranslationReader $translationReader,
        iterable $catalogueLoaders,
        string $targetPath
    ) {
        $this->translationWriter = $translationWriter;
        $this->translationReader = $translationReader;
        $this->catalogueLoaders = $catalogueLoaders;
        $this->targetPath = $targetPath;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $loaders = implode(', ', $this->getLoadersNames());
        $availableFormats = implode(', ', $this->translationWriter->getFormats());
        $this
            ->addOption(
                'locale',
                'l',
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                'The locale names for which the translations should be dumped'
            )
            ->addOption(
                'source',
                's',
                InputOption::VALUE_REQUIRED,
                'Translation source. Available sources: ' . $loaders,
                'database'
            )
            ->addOption(
                'new-only',
                null,
                InputOption::VALUE_NONE,
                'Determinate whether only new translations should be applied.',
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output file format. Available formats: ' . implode(', ', $this->translationWriter->getFormats()),
                'yml'
            )
            ->setDescription('Dumps the translations for the given languages in separate formats.')
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command dumps the translations for the given languages in separate formats.
The files will be dumped to the <info>{$this->targetPath}</info> directory.

The <info>--locale</info> option used to specify locales should be dumped:

  <info>php %command.full_name% --locale=<locale1> --locale=<locale2> --locale=<localeN></info>

The <info>--new-only</info> option can be used to determine whether only new translations should be applied
to existing dumped files:

  <info>php %command.full_name% --locale=<locale> --new-only</info>

The <info>--source</info> option can be used to select the source should be loaded from:

  <info>php %command.full_name% --locale=<locale> --source=<source></info>

Allowed values: {$loaders}. Default value: <info>database</info>.

The <info>--format</info> option can be used to set the output file format:

  <info>php %command.full_name% --locale=<locale> --format=<format></info>

Available formats: {$availableFormats}. Default value: <info>yml</info>
HELP
            )
            ->addUsage('--locale=<locale>')
            ->addUsage('--locale=<locale1> --locale=<locale1> --locale=<localeN>')
            ->addUsage('--locale=<locale> --new-only')
            ->addUsage('--locale=<locale> --source=<source>')
            ->addUsage('--locale=<locale> --format=<format>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $loaderName = $input->getOption('source');
        $format = $input->getOption('format');

        $loader = null;
        foreach ($this->catalogueLoaders as $catalogueLoader) {
            if ($catalogueLoader->getLoaderName() === $loaderName) {
                $loader = $catalogueLoader;
                break;
            }
        }
        if (null === $loader) {
            $symfonyStyle->error(
                sprintf(
                    'Given translation loader \'%s\' is not available. Available loaders: %s',
                    $loaderName,
                    implode(', ', $this->getLoadersNames())
                )
            );

            return 1;
        }

        $hasErrors = false;
        $newOnly = $input->getOption('new-only');
        foreach ($input->getOption('locale') as $locale) {
            $result = $this->dumpLocale($loader, $locale, $format, $newOnly, $symfonyStyle);
            if (!$result) {
                $hasErrors = true;
            }
        }

        return $hasErrors ? 1 : 0;
    }

    private function dumpLocale(
        CatalogueLoaderInterface $loader,
        string $locale,
        string $format,
        bool $newOnly,
        SymfonyStyle $symfonyStyle
    ): bool {
        $symfonyStyle->section(sprintf('Dump the \'%s\' locale', $locale));
        if ($this->hasFilesNotInSelectedFormat($locale, $format)) {
            $symfonyStyle->error(
                sprintf(
                    'The translation directory have the dumped files for the \'%s\' locale not in \'%s\' format',
                    $locale,
                    $format
                )
            );

            return false;
        }

        $catalogue = $loader->getCatalogue($locale);
        $symfonyStyle->text(sprintf('Found the following domains for <info>"%s"</info> locale:', $locale));
        $this->showDomainsInfo($symfonyStyle, $catalogue->getDomains());

        if ($newOnly) {
            $existingCatalogue = $this->loadExistingCatalogue($locale);
            $mergeConflicts = $this->mergeCatalogues($existingCatalogue, $catalogue);
            $catalogue = $existingCatalogue;
            if (count($mergeConflicts)) {
                $this->showMergeConflicts($symfonyStyle, $mergeConflicts);
            }
        }

        $symfonyStyle->text(
            sprintf('Dump data in <info>%s</info> format to the <info>%s</info> path.', $format, $this->targetPath)
        );
        $this->translationWriter->write($catalogue, $format, ['path' => $this->targetPath]);

        return true;
    }

    private function getLoadersNames(): array
    {
        $loaders = [];
        foreach ($this->catalogueLoaders as $catalogueLoader) {
            $loaders[] = $catalogueLoader->getLoaderName();
        }

        return $loaders;
    }

    private function showDomainsInfo(SymfonyStyle $symfonyStyle, array $domains): void
    {
        $rows = [];
        foreach ($domains as $domain) {
            $rows[] = [$domain];
        }
        $symfonyStyle->table(['Domain name'], $rows);
    }

    private function showMergeConflicts(SymfonyStyle $symfonyStyle, $conflicts): void
    {
        $symfonyStyle->info('Found the next translations keys that have another value:');
        $rows = [];
        foreach ($conflicts as $domain => $translations) {
            foreach ($translations as $key => [$oldValue, $newValue]) {
                $rows[] = [$domain, $key, $oldValue, $newValue];
            }
        }
        $symfonyStyle->table(['Domain name', 'Translation key', 'Old value', 'New value'], $rows);
    }

    private function hasFilesNotInSelectedFormat(string $locale, string $format): bool
    {
        // filename format is 'domain.locale.ext'
        $finder = Finder::create()
            ->files()
            ->name('*.' . $locale . '.*')
            ->notName('*.' . $locale . '.' . $format)
            ->in($this->targetPath);

        return $finder->count() > 0;
    }

    private function loadExistingCatalogue(string $locale): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $this->translationReader->read($this->targetPath, $catalogue);

        return $catalogue;
    }

    private function mergeCatalogues(MessageCatalogue $existingCatalogue, MessageCatalogue $newDataCatalogue): array
    {
        $conflicts = [];
        foreach ($newDataCatalogue->getDomains() as $domain) {
            foreach ($newDataCatalogue->all($domain) as $key => $value) {
                if ($existingCatalogue->has($key, $domain)) {
                    $oldValue = $existingCatalogue->get($key, $domain);
                    if ($value !== $oldValue) {
                        $conflicts[$domain][$key] = [$oldValue, $value];
                    }
                } else {
                    $existingCatalogue->add([$key => $value], $domain);
                }
            }
        }

        return $conflicts;
    }
}
