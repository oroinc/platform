<?php

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
 * Dumps translation messages and optionally uploads them to third-party service
 */
class OroTranslationPackCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:translation:pack';

    /** @var string */
    protected $path;

    /** @var TranslationPackDumper */
    private $translationPackDumper;

    /** @var TranslationServiceProvider */
    private $translationServiceProvider;

    /** @var TranslationPackageProvider */
    private $translationPackageProvider;

    /** @var string */
    private $kernelProjectDir;

    /** @var TranslationAdaptersCollection */
    private $translationAdaptersCollection;

    /**
     * @param TranslationPackDumper $translationPackDumper
     * @param TranslationServiceProvider $translationServiceProvider
     * @param TranslationPackageProvider $translationPackageProvider
     * @param TranslationAdaptersCollection $translationAdaptersCollection
     * @param string $kernelProjectDir
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump translation messages and optionally upload them to third-party service')
            ->setDefinition(
                [
                    new InputArgument('project', InputArgument::REQUIRED, 'The project [e.g Oro, OroCRM etc]'),
                    new InputArgument(
                        'locale',
                        InputArgument::OPTIONAL,
                        'The locale for creating language pack [en by default]',
                        'en'
                    ),
                    new InputArgument(
                        'adapter',
                        InputArgument::OPTIONAL,
                        'Uploader adapter, representing third-party service API, config value will be used if empty'
                    ),
                    new InputOption(
                        'project-id',
                        'i',
                        InputOption::VALUE_REQUIRED,
                        'API project ID'
                    ),
                    new InputOption(
                        'api-key',
                        'k',
                        InputOption::VALUE_REQUIRED,
                        'API key'
                    ),
                    new InputOption(
                        'upload-mode',
                        'm',
                        InputOption::VALUE_OPTIONAL,
                        'Uploader mode: add or update',
                        'add'
                    ),
                    new InputOption(
                        'output-format',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Override the default output format',
                        'yml'
                    ),
                    new InputOption(
                        'path',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Dump destination (or upload source), relative to %kernel.project_dir%',
                        '/var/language-pack/'
                    ),
                    new InputOption(
                        'dump',
                        null,
                        InputOption::VALUE_NONE,
                        'Create language pack for uploading to translation service'
                    ),
                    new InputOption(
                        'upload',
                        null,
                        InputOption::VALUE_NONE,
                        'Upload language pack to translation service'
                    ),
                    new InputOption(
                        'download',
                        null,
                        InputOption::VALUE_NONE,
                        'Download all language packs from project at translation service'
                    ),
                    new InputOption(
                        'show',
                        null,
                        InputOption::VALUE_NONE,
                        'Show all enabled project namespaces for dump/load translations packages.'
                    ),
                ]
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command extract translation files for each bundle in
specified vendor namespace(project) and creates language pack that's placed at
%kernel.project_dir%/var/language-pack

    <info>php %command.full_name% --dump OroCRM</info>
    <info>php %command.full_name% --upload OroCRM</info>
    <info>php %command.full_name% --show project</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

    /**
     * @param TranslationServiceProvider $translationService
     * @param string                     $mode
     * @param array                      $languagePackPath one or few dirs
     *
     * @return void
     */
    protected function upload(TranslationServiceProvider $translationService, $mode, $languagePackPath)
    {
        if ('update' == $mode) {
            $translationService->update($languagePackPath);
        } else {
            $translationService->upload($languagePackPath);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function download(InputInterface $input, OutputInterface $output)
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return TranslationServiceProvider
     */
    protected function getTranslationService(InputInterface $input, OutputInterface $output)
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
     * @param InputInterface $input
     *
     * @throws \RuntimeException
     * @return APIAdapterInterface
     */
    protected function getAdapterFromInput(InputInterface $input)
    {
        $adapterOption = $input->getArgument('adapter');
        if (null === $adapterOption) {
            return false;
        }

        $adapterService = $this->translationAdaptersCollection->getAdapter($adapterOption);
        if (!$adapterService) {
            throw new \RuntimeException('Invalid adapter name given');
        }

        return $adapterService;
    }

    /**
     * Performs dump operation
     *
     * @param string          $projectNamespace
     * @param string          $locale
     * @param OutputInterface $output
     * @param string          $outputFormat
     *
     * @return bool
     */
    protected function dump($projectNamespace, $locale, OutputInterface $output, $outputFormat)
    {
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

    /**
     * Return lang pack location
     *
     * @param string      $projectNamespace
     * @param null|string $bundleName
     *
     * @return string
     */
    protected function getLangPackDir($projectNamespace, $bundleName = null)
    {
        $path = $this->path . $projectNamespace . DIRECTORY_SEPARATOR;

        if (!is_null($bundleName)) {
            $path .= $bundleName . DIRECTORY_SEPARATOR . 'translations';
        }

        return $path;
    }

    /**
     * Show enabled project namespaces.
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    private function showEnabledProject(OutputInterface $output)
    {
        $output->writeln('Enabled project namespaces for dump/load translations packages:');

        foreach ($this->translationPackageProvider->getInstalledPackages() as $packageName) {
            $output->writeln(sprintf('> <info>%s</info>', $packageName));
        }
    }
}
