<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command that checks whether the application meets system requirements.
 */
class CheckRequirementsCommand extends Command
{
    protected static $defaultName = 'oro:check-requirements';

    /** @var string */
    private $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Checks that the application meets the system requirements.')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command checks that the application meets the system requirements.

By default this command shows only errors, but you can specify the verbosity level to see warnings
and information messages, e.g.:

  <info>php %command.full_name% -v</info>
or
  <info>php %command.full_name% -vv</info>

The process exit code will be 0 if all requirements are met and 1 if at least one requirement is not fulfilled.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
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
    protected function renderTable(array $requirements, $header, OutputInterface $output)
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
