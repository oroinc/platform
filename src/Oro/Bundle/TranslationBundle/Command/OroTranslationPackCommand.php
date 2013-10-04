<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OroTranslationPackCommand extends ContainerAwareCommand
{
    const DEFAULT_ADAPTER = 'crowdin';

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
                        'default-locale',
                        InputArgument::OPTIONAL,
                        'The locale for creating language pack [en by default]',
                        'en'
                    ),
                    new InputArgument(
                        'adapter',
                        InputArgument::OPTIONAL,
                        'Uploader adapter, representing third-party service API',
                        self::DEFAULT_ADAPTER
                    ),
                    new InputOption(
                        'output-format',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Override the default output format',
                        'yml'
                    ),
                    new InputOption(
                        'dump',
                        null,
                        InputOption::VALUE_NONE,
                        'Create language pack for  uploading to translation service'
                    ),
                    new InputOption(
                        'upload',
                        null,
                        InputOption::VALUE_NONE,
                        'Create language pack for  uploading to translation service'
                    ),
                )
            )
            ->setDescription('Performs operations with translation pack')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command extract translation files for each bundle in
specified vendor namespace(project) and creates language pack that's placed at
%kernel.root_dir%/Resources/language-pack

    <info>php %command.full_name% --dump OroCRM</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check presence of action
        if ($input->getOption('dump') !== true) {
            $output->writeln('<info>You must choose action: e.g --dump</info>');

            return 1;
        }

        if ($input->getOption('dump') === true) {
            $this->dump($input, $output);
        }

        if ($input->getOption('upload') === true) {
            $this->upload($input, $output);
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function upload(InputInterface $input, OutputInterface $output)
    {
        $languagePackPath = $this->getLangPackDir($input->getArgument('project'));
        $finder = Finder::create()->files()->name('*.yml')->in($languagePackPath);


    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function dump(InputInterface $input, OutputInterface $output)
    {
        $projectNamespace = $input->getArgument('project');
        $defaultLocale    = $input->getArgument('default-locale');

        $container = $this->getContainer();
        $bundles   = $container->get('kernel')->getBundles();
        $writer    = $container->get('translation.writer');

        foreach ($bundles as $bundle) {
            $namespaceParts = explode('\\', $bundle->getNamespace());
            if ($namespaceParts && reset($namespaceParts) === $projectNamespace) {
                $bundleLanguagePackPath = $container->getParameter('kernel.root_dir')
                    . '/Resources/language-pack/' . $projectNamespace . '/' . $bundle->getName()
                    . '/translations';

                if (!is_dir($bundleLanguagePackPath)) {
                    $this->createDirectory($bundleLanguagePackPath);
                }

                $operation = $this->getMergedTranslations($defaultLocale, $bundle);
                $output->writeln(
                    sprintf(
                        'Writing files for <info>%s</info>',
                        $bundle->getName()
                    )
                );
                $writer->writeTranslations(
                    $operation->getResult(),
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
     * @param $projectNamespace
     * @param null $bundleName
     * @return string
     */
    protected function getLangPackDir($projectNamespace, $bundleName = null)
    {
        $path = $this->getContainer()->getParameter('kernel.root_dir') .
            '/Resources/language-pack/' . $projectNamespace . '/';

        if (!is_null($bundleName)) {
            $path .= $bundleName . '/translations';
        }

        return  $path;
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
     * @return MergeOperation
     */
    protected function getMergedTranslations($defaultLocale, BundleInterface $bundle)
    {
        $bundleTransPath = $bundle->getPath() . '/Resources/translations';
        $bundleViewsPath = $bundle->getPath() . '/Resources/views/';

        $container = $this->getContainer();
        $extractor = $container->get('translation.extractor');
        $loader    = $container->get('translation.loader');

        $currentCatalogue   = new MessageCatalogue($defaultLocale);
        $extractedCatalogue = new MessageCatalogue($defaultLocale);
        if (is_dir($bundleViewsPath)) {
            $extractor->extract($bundleViewsPath, $extractedCatalogue);
        }
        if (is_dir($bundleTransPath)) {
            $loader->loadMessages($bundleTransPath, $currentCatalogue);
        }
        $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);

        return $operation;
    }
}
