<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks that the environment meets the application requirements.
 */
class CheckRequirementsCommand extends Command
{
    protected static $defaultName = 'oro:check-requirements';

    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Checks that the environment meets the application requirements.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command checks that the environment meets the application requirements.

  <info>php %command.full_name%</info>

By default this command shows only errors, but you can increase the verbosity
to see warnings and informational messages as well:

  <info>php %command.full_name% -v</info>
  <info>php %command.full_name% -vv</info>

The command will return 0 on exit if all application requirements are met
and it will return 1 if some of the requirements are not fulfilled.

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Check system requirements');

        $requirements = $this->getRequirements($input);
        $this->renderTable($requirements->getMandatoryRequirements(), 'Mandatory requirements', $output);
        $this->renderTable($requirements->getPhpIniRequirements(), 'PHP settings', $output);
        $this->renderTable($requirements->getOroRequirements(), 'Oro specific requirements', $output);
        $this->renderTable($requirements->getRecommendations(), 'Optional recommendations', $output);

        $exitCode = 0;
        $numberOfFailedRequirements = count($requirements->getFailedRequirements());
        if ($numberOfFailedRequirements > 0) {
            $exitCode = 1;
            if ($numberOfFailedRequirements > 1) {
                $output->writeln(sprintf(
                    '<error>Found %d not fulfilled requirements</error>',
                    $numberOfFailedRequirements
                ));
            } else {
                $output->writeln('<error>Found 1 not fulfilled requirement</error>');
            }
        } else {
            $output->writeln('<info>The application meets all mandatory requirements</info>');
        }

        return $exitCode;
    }

    /**
     * @param InputInterface $input
     *
     * @return \OroRequirements
     */
    protected function getRequirements(InputInterface $input)
    {
        if (!class_exists('OroRequirements')) {
            require_once $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'OroRequirements.php';
        }

        return new \OroRequirements($input->getOption('env'));
    }

    /**
     * @param \Requirement[]  $requirements
     * @param string          $header
     * @param OutputInterface $output
     */
    protected function renderTable(array $requirements, string $header, OutputInterface $output): void
    {
        $rows = [];
        $verbosity = $output->getVerbosity();
        foreach ($requirements as $requirement) {
            if ($requirement->isFulfilled()) {
                if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $rows[] = ['OK', $requirement->getTestMessage()];
                }
            } elseif ($requirement->isOptional()) {
                if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                    $rows[] = ['WARNING', $requirement->getHelpText()];
                }
            } else {
                if ($verbosity >= OutputInterface::VERBOSITY_NORMAL) {
                    $rows[] = ['ERROR', $requirement->getHelpText()];
                }
            }
        }

        if (!empty($rows)) {
            $table = new Table($output);
            $table
                ->setHeaders(['Check  ', $header])
                ->setRows([]);
            foreach ($rows as $row) {
                $table->addRow($row);
            }
            $table->render();
        }
    }
}
