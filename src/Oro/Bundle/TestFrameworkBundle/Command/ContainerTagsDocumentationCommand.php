<?php
declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Command;

use Oro\Bundle\TestFrameworkBundle\Provider\ContainerTagsDocumentationInformationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists available documentation for dependency injection tags.
 */
class ContainerTagsDocumentationCommand extends Command
{
    protected static $defaultName = 'oro:debug:container:tag-documentation';

    private ContainerTagsDocumentationInformationProvider $docInfoProvider;

    public function __construct(
        ContainerTagsDocumentationInformationProvider $containerTagsDocumentationInformationProvider
    ) {
        $this->docInfoProvider = $containerTagsDocumentationInformationProvider;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('without-documentation', null, InputOption::VALUE_NONE, 'List undocumented tags')
            ->addOption('with-documentation', null, InputOption::VALUE_NONE, 'List documented tags')
            ->addOption(
                'skip-code-examples',
                null,
                InputOption::VALUE_NONE,
                'Skip documentation where tag is mentioned in code example blocks'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Tag name patterns to exclude'
            )
            ->addOption(
                'included',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Tag name patterns to include'
            )
            ->setDescription('Lists available documentation for dependency injection tags.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists available documentation
for dependency injection tags.

  <info>php %command.full_name%</info>

The <info>--without-documentation</info> and <info>--with-documentation</info> options can be used
to display only undocumented or documented tags respectively:

  <info>php %command.full_name% --without-documentation</info>
  <info>php %command.full_name% --with-documentation</info>

Use <info>--skip-code-examples</info> option not to consider code examples as documentation:

  <info>php %command.full_name% --skip-code-examples</info>

The <info>--exclude</info> and <info>--included</info> options can be used
to exclude or include certain tags respectively:

  <info>php %command.full_name% --exclude=<pattern1> --exclude=<pattern2> --exclude=<patternN></info>
  <info>php %command.full_name% --included=<pattern1> --included=<pattern2> --included=<patternN></info>

HELP
            )
            ->addUsage('--without-documentation')
            ->addUsage('--with-documentation')
            ->addUsage('--skip-code-examples')
            ->addUsage('--exclude=<pattern1> --exclude=<pattern2> --exclude=<patternN>')
            ->addUsage('--included=<pattern1> --included=<pattern2> --included=<patternN>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $includedTags = (array)$input->getOption('included');
        $excludedTags = (array)$input->getOption('exclude');
        $skipCodeExamples = (bool)$input->getOption('skip-code-examples');
        $withDocs = (bool)$input->getOption('with-documentation');
        $withoutDocs = (bool)$input->getOption('without-documentation');

        $oroTags = $this->docInfoProvider->getOroTags($includedTags, $excludedTags);
        $io = new SymfonyStyle($input, $output);

        $documentationInfo = $this->docInfoProvider
            ->getTagsDocumentationInformation($oroTags, $skipCodeExamples);
        ksort($documentationInfo);
        $rows = $this->getAsTableRows($documentationInfo, $withDocs, $withoutDocs);

        if (!$rows) {
            $io->writeln('<info>Tags information is empty</info>');
        } else {
            $io->table(['Tag', 'Documentation Path'], $rows);
            $io->text('Total: ' . count($rows));
        }

        return 0;
    }

    protected function getAsTableRows(array $documentationInfo, bool $withDocs, bool $withoutDocs): array
    {
        $rows = [];
        $noSkip = !$withDocs && !$withoutDocs;
        $includeWithDoc = $noSkip || $withDocs;
        $includeWithoutDoc = $noSkip || $withoutDocs;
        foreach ($documentationInfo as $tag => $docs) {
            foreach ($docs as &$doc) {
                $doc = '<info>'
                    . str_replace($this->docInfoProvider->getInstallDir() . '/', '', $doc)
                    . '</info>';
            }
            unset($doc);

            if ($docs) {
                if ($includeWithDoc) {
                    $rows[] = ['<info>' . $tag . '</info>', implode(PHP_EOL, $docs)];
                }
            } elseif ($includeWithoutDoc) {
                $rows[] = ['<error>' . $tag . '</error>', 'N/A'];
            }
        }

        return $rows;
    }
}
