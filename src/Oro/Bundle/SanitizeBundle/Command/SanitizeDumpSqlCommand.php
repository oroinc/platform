<?php

declare(strict_types=1);

namespace Oro\Bundle\SanitizeBundle\Command;

use Oro\Bundle\SanitizeBundle\Tools\SanitizeSqlLoader;
use Oro\Bundle\SanitizeBundle\Tools\SqlSyntaxValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command to dump SQL queries that perform data sanitization based on predefined rules or raw SQLs.
 */
#[AsCommand(
    name: 'oro:sanitize:dump-sql',
    description: 'Dumps DB sanitizing SQLs related to rules assigned to entities and their fields'
)]
class SanitizeDumpSqlCommand extends Command
{
    public function __construct(
        private SanitizeSqlLoader $sanitizeSqlLoader,
        private SqlSyntaxValidator $sqlSyntaxValidator
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure()
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'File name to dump sanitizing SQLs')
            ->addOption('no-guessing', null, InputOption::VALUE_NONE, 'Skip guessing the sanitizing rule for a field')
            ->addOption('skip-validate-sql', null, InputOption::VALUE_NONE, 'Skip syntax validation of dumped SQLs')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps DB sanitizing SQL queries regarding to rules assigned to entities
and their fields via the entity config or dedicated rule files.
Dumped SQLs are saved to a file if that file is provided as an argument or shown in the console output.

Sanitizing rules guessing can be disabled with <info>--no-guessing</info> option.

    <info>php %command.full_name% --no-guessing</info>

SQL dump files are validated by default using the database driver.
Failed queries are marked with comments and highlighted in the console output.
SQL validation can be skipped with <info>--skip-validate-sql</info>.

    <info>php %command.full_name% --skip-validate-sql</info>

HELP
            )
            ->addUsage('<file>')
            ->addUsage('--no-guessing --skip-validate-sql <file>');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('file');
        $filesystem = new Filesystem();
        if (!empty($filename)) {
            try {
                $filesystem->touch([$filename]);
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    PHP_EOL . '<error>Unable to write to "%s" file</error>' . PHP_EOL,
                    $filename
                ));
            }
        }

        $sqls = $this->sanitizeSqlLoader->load(!$input->getOption('no-guessing'));
        $buildIssues = $this->sanitizeSqlLoader->getLastIssueMessages();
        if (count($buildIssues)) {
            $output->writeln(
                '<comment>When building sanitizing SQL queries, the following issues/errors were uncovered</comment>'
            );
            $output->writeln(implode('', array_map(function ($issue) {
                return sprintf(PHP_EOL . "<error>%s</error>" . PHP_EOL, $issue);
            }, $buildIssues)));

            return Command::FAILURE;
        }

        $validationErrors = !$input->getOption('skip-validate-sql')
            ? $validationErrors = $this->sqlSyntaxValidator->validate($sqls)
            : [];

        if (!empty($filename) && empty($validationErrors)) {
            $filesystem->dumpFile($filename, $this->getSqlOutput($sqls, $validationErrors));
        } else {
            $output->write(PHP_EOL . $this->getSqlOutput($sqls, $validationErrors, true) . PHP_EOL);
        }

        if (count($validationErrors)) {
            $output->writeln(
                '<comment>The sanitizing SQL queries validation has detected the following errors:</comment>'
            );
            $output->writeln(implode('', array_map(function ($error) {
                return sprintf(PHP_EOL . "<error>%s</error>" . PHP_EOL, $error);
            }, $validationErrors)));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getSqlOutput(
        array $sqls,
        array $validationErrors,
        bool $applyOutputFormatting = false
    ): string {
        $sqlOutput = '';
        $hasTruncateCascade = false;

        foreach ($sqls as $index => $sql) {
            $highlightSql = false;

            if (preg_match('/truncate.*?(cascade)/i', $sql)) {
                $hasTruncateCascade = true;
                $highlightSql = true;
            }

            $hasErrors = array_key_exists($index, $validationErrors);
            $sql = $this->normalizeSql($sql, $hasErrors);

            if ($applyOutputFormatting) {
                if ($hasErrors) {
                    $sql = '<error>' . $sql . '</error>';
                } elseif ($highlightSql) {
                    $sql = '<comment>' . $sql . '</comment>';
                } else {
                    $sql = '<info>' . $sql . '</info>';
                }
            }

            $sqlOutput .= $sql . PHP_EOL;
        }

        if ($hasTruncateCascade) {
            $sqlOutput = '-- !!!Exercise caution when using TRUNCATE queries with CASCADE options --'
                . PHP_EOL . $sqlOutput;
            if ($applyOutputFormatting) {
                $sqlOutput = '<comment>' . $sqlOutput . '</comment>';
            }
        }

        return $sqlOutput;
    }

    private function normalizeSql(string $sql, bool $hasErrors): string
    {
        $normilizedSql = '';
        $sql = str_replace(["\n", "\r\n", "\r"], PHP_EOL, trim($sql, " \n\r\t\v\x00;"));

        $firstCommentIsOut = false;
        foreach (explode(PHP_EOL, $sql) as $sqlLine) {
            if ($hasErrors) {
                $normilizedSql .= (!$firstCommentIsOut ? '-- The query has syntax errors:' . PHP_EOL . '-- ' : '-- ')
                    . $sqlLine . PHP_EOL;
            } else {
                $normilizedSql .= $sqlLine . PHP_EOL;
            }

            $firstCommentIsOut = true;
        }
        $normilizedSql = trim($normilizedSql);

        if (!$this->sqlSyntaxValidator->isSqlPurelyComment($normilizedSql)) {
            $normilizedSql .= (strpos($sqlLine, '--') !== false ? PHP_EOL : '') . ';';
        }

        return $normilizedSql;
    }
}
