<?php

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Oro\Bundle\TestFrameworkBundle\Provider\ContainerTagsDocumentationInformationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Displays documented information for dependency injection tags
 */
class ContainerTagsDocumentationCommand extends Command
{
    protected static $defaultName = 'oro:debug:container:tag-documentation';

    private const OPTION_WITHOUT_DOCUMENTATION = 'without-documentation';
    private const OPTION_WITH_DOCUMENTATION = 'with-documentation';
    private const OPTION_SKIP_CODE_EXAMPLES = 'skip-code-examples';
    private const OPTION_EXCLUDE = 'exclude';
    private const OPTION_INCLUDED = 'included';

    /** @var ContainerTagsDocumentationInformationProvider */
    private $containerTagsDocumentationInformationProvider;

    /**
     * @param ContainerTagsDocumentationInformationProvider $containerTagsDocumentationInformationProvider
     */
    public function __construct(
        ContainerTagsDocumentationInformationProvider $containerTagsDocumentationInformationProvider
    ) {
        $this->containerTagsDocumentationInformationProvider = $containerTagsDocumentationInformationProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

        $oroTags = $this->containerTagsDocumentationInformationProvider->getOroTags($includedTags, $excludedTags);
        $io = new SymfonyStyle($input, $output);

        $documentationInfo = $this->containerTagsDocumentationInformationProvider
            ->getTagsDocumentationInformation($oroTags, $skipCodeExamples);
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
        foreach ($documentationInfo as $tag => $docs) {
            foreach ($docs as &$doc) {
                $doc = '<info>'
                    . str_replace($this->containerTagsDocumentationInformationProvider->getInstallDir() . '/', '', $doc)
                    . '</info>';
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
}
