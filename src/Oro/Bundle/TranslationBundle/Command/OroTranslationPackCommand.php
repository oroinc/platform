<?php

namespace Oro\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OroTranslationPackCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:translation:pack')
            ->setDefinition(
                array(
                    new InputArgument('project', InputArgument::REQUIRED, 'The project [e.g Oro, OroCRM etc]'),
                    new InputArgument(
                        'default-locale',
                        InputArgument::OPTIONAL,
                        'The locale for creating language pack [en by default]',
                        'en'
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
            $projectNamespace = $input->getArgument('project');
            $defaultLocale    = $input->getArgument('default-locale');

            $bundles   = $this->getContainer()->get('kernel')->getBundles();
            $writer    = $this->getContainer()->get('translation.writer');
            $extractor = $this->getContainer()->get('translation.extractor');
            $loader    = $this->getContainer()->get('translation.loader');
            $fs        = new Filesystem();

            foreach ($bundles as $bundle) {
                $namespaceParts = explode('\\', $bundle->getNamespace());
                if ($namespaceParts && reset($namespaceParts) === $projectNamespace) {
                    $bundleTransPath        = $bundle->getPath() . '/Resources/translations';
                    $bundleViewsPath        = $bundle->getPath() . '/Resources/views/';
                    $bundleLanguagePackPath = $this->getContainer()->getParameter('kernel.root_dir')
                        . '/Resources/language-pack/' . $projectNamespace . '/' . $bundle->getName()
                        . '/translations';

                    if (!is_dir($bundleLanguagePackPath)) {
                        $fs->mkdir($bundleLanguagePackPath);
                    }
                    $currentCatalogue   = new MessageCatalogue($defaultLocale);
                    $extractedCatalogue = new MessageCatalogue($defaultLocale);
                    if (is_dir($bundleViewsPath)) {
                        $extractor->extract($bundleViewsPath, $extractedCatalogue);
                    }
                    if (is_dir($bundleTransPath)) {
                        $loader->loadMessages($bundleTransPath, $currentCatalogue);
                    }
                    $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);
                    foreach ($operation->getDomains() as $domain) {
                        $output->writeln(
                            sprintf(
                                PHP_EOL . 'Writing files for <info>%s: %s</info>',
                                $bundle->getName(),
                                $domain
                            )
                        );
                        $writer->writeTranslations(
                            $operation->getResult(),
                            $input->getOption('output-format'),
                            array('path' => $bundleLanguagePackPath)
                        );
                    }
                }
            }
        }

        return 0;
    }
}
