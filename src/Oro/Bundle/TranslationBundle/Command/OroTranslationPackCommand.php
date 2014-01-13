<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\TranslationBundle\Provider\AbstractAPIAdapter;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;

class OroTranslationPackCommand extends ContainerAwareCommand
{
    /** @var string */
    protected $path;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:pack')
            ->setDescription('Dump translation messages and optionally upload them to third-party service')
            ->setDefinition(
                array(
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
                        InputOption::VALUE_OPTIONAL,
                        'API project ID, by default equal to project name'
                    ),
                    new InputOption(
                        'api-key',
                        'k',
                        InputOption::VALUE_OPTIONAL,
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
                        'Dump destination (or upload source), relative to %kernel.root_dir%',
                        '/Resources/language-pack/'
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
                )
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command extract translation files for each bundle in
specified vendor namespace(project) and creates language pack that's placed at
%kernel.root_dir%/Resources/language-pack

    <info>php %command.full_name% --dump OroCRM</info>
    <info>php %command.full_name% --upload OroCRM</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        // check presence of action
        $modeOption = false;
        foreach (['dump', 'upload', 'download'] as $option) {
            $modeOption = $modeOption || $input->getOption($option) === true;
        }

        if (!$modeOption) {
            $output->writeln('<info>You must choose action: e.g --dump, --upload or --download</info>');
            return 1;
        }

        $this->path = $this->getContainer()->getParameter('kernel.root_dir')
            . str_replace('//', '/', $input->getOption('path') . '/');

        if ($input->getOption('dump') === true) {
            $this->dump($input, $output);
        }

        if ($input->getOption('upload') === true) {
            $this->upload($input, $output);
        }

        if ($input->getOption('download') === true) {
            $this->download($input, $output);
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function upload(InputInterface $input, OutputInterface $output)
    {
        $projectName      = $input->getArgument('project');
        $languagePackPath = $this->getLangPackDir($projectName);

        $translationService = $this->getTranslationService($input, $output);
        $mode = $input->getOption('upload-mode');

        if ($mode == 'update') {
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

        $projects = explode(',', $projectName);
        $languagePackPath = rtrim(
            $this->getLangPackDir(implode('_', $projects)),
            DIRECTORY_SEPARATOR
        ) . '.zip';

        $result = $this
            ->getTranslationService($input, $output)
            ->download($languagePackPath, $projects, $locale);

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
        $service = $this->getContainer()->get('oro_translation.service_provider');
        $service->setLogger(new OutputLogger($output));

        // set non default adapter if comes from input
        if ($adapter = $this->getAdapterFromInput($input)) {
            $service->setAdapter($adapter);
        }

        /*
         * Set project id and api key to adapter anyway if its provided
         */
        $projectId = $input->getOption('project-id');
        if (null !== $projectId) {
            $service->getAdapter()->setProjectId($projectId);
        }
        $apiKey = $input->getOption('api-key');
        if (null !== $apiKey) {
            $service->getAdapter()->setApiKey($apiKey);
        }

        return $service;
    }

    /**
     * @param InputInterface $input
     *
     * @throws \RuntimeException
     * @return AbstractAPIAdapter
     */
    protected function getAdapterFromInput(InputInterface $input)
    {
        $adapterOption = $input->getArgument('adapter');
        if (null === $adapterOption) {
            return false;
        }

        $serviceId = sprintf('oro_translation.uploader.%s_adapter', $adapterOption);
        if (!$this->getContainer()->has($serviceId)) {
            throw new \RuntimeException('Invalid adapter name given');
        }
        return $this->getContainer()->get($serviceId);
    }

    /**
     * Performs dump operation
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function dump(InputInterface $input, OutputInterface $output)
    {
        $projectNamespace = $input->getArgument('project');
        $defaultLocale    = $input->getArgument('locale');

        $output->writeln(sprintf('Dumping language pack for <info>%s</info>' . PHP_EOL, $projectNamespace));

        $container = $this->getContainer();
        $bundles   = $container->get('kernel')->getBundles();
        $writer    = $container->get('translation.writer');

        foreach ($bundles as $bundle) {
            $namespaceParts = explode('\\', $bundle->getNamespace());
            if ($namespaceParts && reset($namespaceParts) === $projectNamespace) {
                $bundleLanguagePackPath = $this->getLangPackDir($projectNamespace, $bundle->getName());

                if (!is_dir($bundleLanguagePackPath)) {
                    $this->createDirectory($bundleLanguagePackPath);
                }

                $messageCatalog = $this->getMergedTranslations($defaultLocale, $bundle);
                $output->writeln(
                    sprintf(
                        'Writing files for <info>%s</info>',
                        $bundle->getName()
                    )
                );
                $writer->writeTranslations(
                    $messageCatalog,
                    $input->getOption('output-format'),
                    array('path' => $bundleLanguagePackPath)
                );
            }
        }

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
     * Create directory using Filesystem object
     *
     * @param string $dirPath
     */
    protected function createDirectory($dirPath)
    {
        $fs = new Filesystem();
        $fs->mkdir($dirPath);
    }

    /**
     * Merge current and extracted translations
     *
     * @param string          $defaultLocale
     * @param BundleInterface $bundle
     *
     * @return MessageCatalogue
     */
    protected function getMergedTranslations($defaultLocale, BundleInterface $bundle)
    {
        $bundleTransPath = $bundle->getPath() . '/Resources/translations';
        $bundleViewsPath = $bundle->getPath() . '/Resources/views/';

        $container = $this->getContainer();
        $loader    = $container->get('translation.loader');

        $currentCatalogue   = new MessageCatalogue($defaultLocale);
        $extractedCatalogue = new MessageCatalogue($defaultLocale);
        if (is_dir($bundleViewsPath)) {
            $extractor = $container->get('translation.extractor');
            $extractor->extract($bundleViewsPath, $extractedCatalogue);
        }
        if (is_dir($bundleTransPath)) {
            $loader->loadMessages($bundleTransPath, $currentCatalogue);
        }

        $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);
        $messageCatalogue = $operation->getResult();

        return $messageCatalogue;
    }
}
