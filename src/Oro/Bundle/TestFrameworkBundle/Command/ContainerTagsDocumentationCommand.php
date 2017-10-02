<?php

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Oro\Bundle\TestFrameworkBundle\Provider\ContainerTagsDocumentationInformationProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContainerTagsDocumentationCommand extends ContainerAwareCommand
{
    const OPTION_WITHOUT_DOCUMENTATION = 'without-documentation';
    const OPTION_WITH_DOCUMENTATION = 'with-documentation';
    const OPTION_SKIP_CODE_EXAMPLES = 'skip-code-examples';
    const OPTION_EXCLUDE = 'exclude';
    const OPTION_INCLUDED = 'included';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:debug:container:tag-documentation')
            ->setDescription('Displays documented information for dependency injection tags')
            ->addOption(
                self::OPTION_WITHOUT_DOCUMENTATION,
                null,
                InputOption::VALUE_NONE,
                'Show list of undocumented tags'
            )
            ->addOption(
                self::OPTION_WITH_DOCUMENTATION,
                null,
                InputOption::VALUE_NONE,
                'Show list of documented tags'
            )
            ->addOption(
                self::OPTION_SKIP_CODE_EXAMPLES,
                null,
                InputOption::VALUE_NONE,
                'Skip documentation where tag is mentioned in code example blocks'
            )
            ->addOption(
                self::OPTION_EXCLUDE,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'List of tag name patterns to exclude'
            )
            ->addOption(
                self::OPTION_INCLUDED,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'List of tag name patterns to include'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $includedTags = (array)$input->getOption(self::OPTION_INCLUDED);
        $excludedTags = (array)$input->getOption(self::OPTION_EXCLUDE);
        $skipCodeExamples = (bool)$input->getOption(self::OPTION_SKIP_CODE_EXAMPLES);
        $withDocs = (bool)$input->getOption(self::OPTION_WITH_DOCUMENTATION);
        $withoutDocs = (bool)$input->getOption(self::OPTION_WITHOUT_DOCUMENTATION);

        $tagsInformationProvider = $this->getTagsInformationProvider();
        $oroTags = $tagsInformationProvider->getOroTags($includedTags, $excludedTags);
        $io = new SymfonyStyle($input, $output);

        $documentationInfo = $tagsInformationProvider->getTagsDocumentationInformation($oroTags, $skipCodeExamples);
        ksort($documentationInfo);
        $rows = $this->getAsTableRows($documentationInfo, $withDocs, $withoutDocs);

        if (!$rows) {
            $io->writeln('<info>Tags information is empty</info>');
        } else {
            $io->table(['Tag', 'Documentation Path'], $rows);
            $io->text('Total: ' . count($rows));
        }
    }

    /**
     * @param array $documentationInfo
     * @param bool $withDocs
     * @param bool $withoutDocs
     * @return array
     */
    protected function getAsTableRows(array $documentationInfo, $withDocs, $withoutDocs): array
    {
        $rows = [];
        $noSkip = !$withDocs && !$withoutDocs;
        $includeWithDoc = $noSkip || $withDocs;
        $includeWithoutDoc = $noSkip || $withoutDocs;
        $tagsInformationProvider = $this->getTagsInformationProvider();
        foreach ($documentationInfo as $tag => $docs) {
            foreach ($docs as &$doc) {
                $doc = '<info>' . str_replace($tagsInformationProvider->getInstallDir() . '/', '', $doc) . '</info>';
            }
            unset($doc);

            if ($docs) {
                if ($includeWithDoc) {
                    $rows[] = [
                        '<info>' . $tag . '</info>',
                        implode(PHP_EOL, $docs)
                    ];
                }
            } elseif ($includeWithoutDoc) {
                $rows[] = [
                    '<error>' . $tag . '</error>',
                    'N/A'
                ];
            }
        }

        return $rows;
    }

    /**
     * @return ContainerTagsDocumentationInformationProvider
     */
    protected function getTagsInformationProvider()
    {
        return $this->getContainer()->get('oro_test.provider.container_tags_documentation_information');
    }
}
