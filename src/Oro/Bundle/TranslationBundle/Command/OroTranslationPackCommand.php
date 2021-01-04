<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Command;

use Oro\Bundle\TranslationBundle\Provider\APIAdapterInterface;
use Oro\Bundle\TranslationBundle\Provider\TranslationAdaptersCollection;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates, uploads or downloads translation packs.
 */
class OroTranslationPackCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:pack';

    protected string $path;
    private string $kernelProjectDir;

    private TranslationPackDumper $translationPackDumper;
    private TranslationServiceProvider $translationServiceProvider;
    private TranslationPackageProvider $translationPackageProvider;
    private TranslationAdaptersCollection $translationAdaptersCollection;

    public function __construct(
        TranslationPackDumper $translationPackDumper,
        TranslationServiceProvider $translationServiceProvider,
        TranslationPackageProvider $translationPackageProvider,
        TranslationAdaptersCollection $translationAdaptersCollection,
        string $kernelProjectDir
    ) {
        $this->translationPackDumper = $translationPackDumper;
        $this->translationServiceProvider = $translationServiceProvider;
        $this->translationPackageProvider = $translationPackageProvider;
        $this->kernelProjectDir = $kernelProjectDir;
        $this->translationAdaptersCollection = $translationAdaptersCollection;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDefinition(
                [
                    new InputArgument('project', InputArgument::REQUIRED, 'Project name'),
                    new InputArgument('locale', InputArgument::OPTIONAL, 'Locale', 'en'),
                    new InputArgument('adapter', InputArgument::OPTIONAL, 'Upload adapter'),
                    new InputOption('project-id', 'i', InputOption::VALUE_REQUIRED, 'Project ID'),
                    new InputOption('api-key', 'k', InputOption::VALUE_REQUIRED, 'API key'),
                    new InputOption('upload-mode', 'm', InputOption::VALUE_OPTIONAL, 'Mode (add or update)', 'add'),
                    new InputOption('output-format', null, InputOption::VALUE_OPTIONAL, 'Format', 'yml'),
                    new InputOption(
                        'path',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Dump destination relative to %kernel.project_dir%',
                        '/var/language-pack/'
                    ),
                    new InputOption('dump', null, InputOption::VALUE_NONE, 'Create a language pack for uploading'),
                    new InputOption('upload', null, InputOption::VALUE_NONE, 'Upload to the translation service'),
                    new InputOption(
                        'download',
                        null,
                        InputOption::VALUE_NONE,
                        'Download all language packs from the translation service'
                    ),
                    new InputOption('show', null, InputOption::VALUE_NONE, 'Show enabled project namespaces'),
                ]
            )
            ->setDescription('Creates, uploads or downloads translation packs.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command extracts translation files for all bundles in
the specified project (vendor namespace) and creates a language pack:

  <info>php %command.full_name% --dump <project></info>

The <info>--path</info> option can be used to override the default target directory
(<comment>%kernel.project_dir%/var/language-pack/</comment>) by specifying a different path
relative to <comment>%kernel.project_dir%</comment>:

  <info>php %command.full_name% --path=<directory> --dump <project></info>
  
Use the <info>--output-format</info> to change the default output format (yml):

  <info>php %command.full_name% --output-format=<format> --dump <project></info>

You can upload the created language pack to a 3-rd party translation service
with the <info>--upload</info> option:

  <info>php %command.full_name% --upload <project></info>

The destination service can be selected by specifying a different adapter as the 3-rd argument:

  <info>php %command.full_name% --upload <project> <locale> <adapter></info>

The <info>--upload-mode</info> option allows to specify whether the language pack should be
simply uploaded to the translation service (<comment>add</comment>), or if the command should first
download the existing translations, merge the new translations with the existing
translations, and upload the result to the translation service (<comment>update</comment>):

  <info>php %command.full_name% --upload --upload-mode=add <project> <locale> <adapter></info>
  <info>php %command.full_name% --upload --upload-mode=update <project> <locale> <adapter></info>

Use the <info>--project-id</info> and <info>--api-key</info> options to supply the translation service credentials:

  <info>php %command.full_name% --project-id=<project-id> --api-key=<api-key> --upload <project></info>

The <info>--show</info> option will print the list of all packages registered in the application:

  <info>php %command.full_name% --show all</info>

This command can also be used to download translations from the translation service
and <options=bold>load them into the application database</> by using the <info>--download</info> option:

  <info>php %command.full_name% --download <project> <locale></info>

HELP
            )
            ->addUsage('--dump <project>')
            ->addUsage('--path=<directory> --dump <project>')
            ->addUsage('--output-format=<format> --dump <project>')
            ->addUsage('--upload <project>')
            ->addUsage('--upload <project> <locale> <adapter>')
            ->addUsage('--upload --upload-mode=add <project> <locale> <adapter>')
            ->addUsage('--upload --upload-mode=update <project> <locale> <adapter>')
            ->addUsage('--project-id=<project-id> --api-key=<api-key> --upload <project>')
            ->addUsage('--show all')
            ->addUsage('--download <project> <locale>')
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        if ($input->getOption('show') === true) {
            $this->showEnabledProject($output);
            return 0;
        }

        // check presence of action
        $modeOption = false;
        foreach (['dump', 'upload', 'download'] as $option) {
            $modeOption = $modeOption || $input->getOption($option) === true;
        }

        if (!$modeOption) {
            $output->writeln('<info>You must choose action: e.g --dump, --upload or --download</info>');
            return 1;
        }

        $this->path = $this->kernelProjectDir
            . str_replace('//', '/', $input->getOption('path') . '/');

        $locale           = $input->getArgument('locale');
        $outputFormat     = $input->getOption('output-format');
        $projectNamespace = $input->getArgument('project');

        $namespaces = [$projectNamespace];

        if ($input->getOption('dump') === true) {
            foreach ($namespaces as $namespace) {
                $this->dump($namespace, $locale, $output, $outputFormat);
            }
        }

        if ($input->getOption('upload') === true) {
            $translationService = $this->getTranslationService($input, $output);

            $langPackDirs = [];
            foreach ($namespaces as $namespace) {
                $langPackDirs[$namespace] = $this->getLangPackDir($namespace);
            }

            $this->upload($translationService, $input->getOption('upload-mode'), $langPackDirs);
        }

        if ($input->getOption('download') === true) {
            $this->download($input, $output);
        }

        return 0;
    }

    protected function upload(
        TranslationServiceProvider $translationService,
        string $mode,
        array $languagePackPath
    ): void {
        if ('update' == $mode) {
            $translationService->update($languagePackPath);
        } else {
            $translationService->upload($languagePackPath);
        }
    }

    protected function download(InputInterface $input, OutputInterface $output): void
    {
        $projectName = $input->getArgument('project');
        $locale      = $input->getArgument('locale');

        $languagePackPath = rtrim(
            $this->getLangPackDir($projectName),
            DIRECTORY_SEPARATOR
        );

        $translationService = $this->getTranslationService($input, $output);

        $result = $translationService->download($languagePackPath, [$projectName], $locale);
        if ($result) {
            $result = $translationService->loadTranslatesFromFile($languagePackPath, $locale);
        }

        $output->writeln(sprintf("Download %s", $result ? 'successful' : 'failed'));
    }

    protected function getTranslationService(InputInterface $input, OutputInterface $output): TranslationServiceProvider
    {
        $this->translationServiceProvider->setLogger(new OutputLogger($output));

        // set non default adapter if comes from input
        $adapter = $this->getAdapterFromInput($input);
        if ($adapter) {
            $this->translationServiceProvider->setAdapter($adapter);
        }

        /*
         * Set project id and api key to adapter anyway if its provided
         */
        $projectId = $input->getOption('project-id');
        if (null !== $projectId) {
            $this->translationServiceProvider->getAdapter()->setProjectId($projectId);
        }
        $apiKey = $input->getOption('api-key');
        if (null !== $apiKey) {
            $this->translationServiceProvider->getAdapter()->setApiKey($apiKey);
        }

        return $this->translationServiceProvider;
    }

    /**
     * @throws \RuntimeException
     */
    protected function getAdapterFromInput(InputInterface $input): ?APIAdapterInterface
    {
        $adapterOption = $input->getArgument('adapter');
        if (null === $adapterOption) {
            return null;
        }

        $adapterService = $this->translationAdaptersCollection->getAdapter($adapterOption);
        if (!$adapterService) {
            throw new \RuntimeException('Invalid adapter name given');
        }

        return $adapterService;
    }

    protected function dump(
        string $projectNamespace,
        string $locale,
        OutputInterface $output,
        string $outputFormat
    ): bool {
        $output->writeln(sprintf('Dumping language pack for <info>%s</info>', $projectNamespace));

        $this->translationPackDumper->setLogger(new OutputLogger($output));

        $languagePackPath = $this->getLangPackDir($projectNamespace);
        $this->translationPackDumper->dump(
            $languagePackPath,
            $projectNamespace,
            $outputFormat,
            $locale
        );

        return true;
    }

    protected function getLangPackDir(string $projectNamespace, ?string $bundleName = null): string
    {
        $path = $this->path . $projectNamespace . DIRECTORY_SEPARATOR;

        if (!is_null($bundleName)) {
            $path .= $bundleName . DIRECTORY_SEPARATOR . 'translations';
        }

        return $path;
    }

    private function showEnabledProject(OutputInterface $output): void
    {
        $output->writeln('Enabled project namespaces for dump/load translations packages:');

        foreach ($this->translationPackageProvider->getInstalledPackages() as $packageName) {
            $output->writeln(sprintf('> <info>%s</info>', $packageName));
        }
    }
}
