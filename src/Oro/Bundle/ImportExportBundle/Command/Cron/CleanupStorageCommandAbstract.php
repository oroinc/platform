<?php
declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Basic logic to delete old temporary import/export files.
 */
abstract class CleanupStorageCommandAbstract extends Command implements CronCommandScheduleDefinitionInterface
{
    protected const DEFAULT_PERIOD = 14; // days

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval (days) to keep the import and export files.'.
                ' Will be removed files older than today-interval.',
                static::DEFAULT_PERIOD
            )
            ->setDescription('Deletes old temporary import/export files.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command deletes old temporary import/export files.
  <info>php %command.full_name%</info>
The <info>--interval</info> option can be used to override the default time period (14 days)
past which the temporary import files are considered old:
  <info>php %command.full_name% --interval=<days></info>
HELP
            )
            ->addUsage('--interval=<days>')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = (int)$input->getOption('interval');

        $from = new \DateTime('@0');
        $to = new \DateTime();
        $to->modify(sprintf('-%d days', $period));

        $files = $this->getFilesForDeletion($from, $to);
        /** @var File $file*/
        foreach ($files as $fileName => $file) {
            $this->deleteFile($file);
            $output->writeln(
                sprintf('<info> File "%s" was removed.</info>', $fileName),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        $output->writeln(sprintf('<info>Were removed "%s" files.</info>', count($files)));

        return 0;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array|File[]
     */
    abstract protected function getFilesForDeletion(\DateTime $from, \DateTime $to): array;

    abstract protected function deleteFile(File $file): void;
}
