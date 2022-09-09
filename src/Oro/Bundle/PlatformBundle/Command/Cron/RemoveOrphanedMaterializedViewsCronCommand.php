<?php

namespace Oro\Bundle\PlatformBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewRemover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cron command which removes orphaned {@see MaterializedView} entities and corresponding materialized views.
 * An orphaned materialized view can appear when it was not removed after usage, for example after an unexpected
 * interruption of a datagrid export that caused loss of the dependent message that should have done a cleanup.
 */
class RemoveOrphanedMaterializedViewsCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    protected static $defaultName = 'oro:cron:platform:materialized-view:remove-orphans';
    protected static $defaultDescription = 'Removes orphaned MaterializedView entities and related materialized views.';

    private MaterializedViewRemover $materializedViewRemover;

    private int $defaultDaysOld;

    public function __construct(MaterializedViewRemover $materializedViewRemover, int $defaultDaysOld)
    {
        $this->materializedViewRemover = $materializedViewRemover;
        $this->defaultDaysOld = $defaultDaysOld;

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'days-old',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of days since the last update to collect materialized views for removal.',
            $this->defaultDaysOld
        );
    }

    public function getDefaultDefinition(): string
    {
        return '0 0 * * *'; // Every midnight.
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $daysOld = $input->getOption('days-old');
        if (!is_numeric($daysOld) || $daysOld <= 0) {
            $symfonyStyle->error(sprintf('Option "days-old" must be a positive number, got "%s"', $daysOld));

            return self::FAILURE;
        }

        $materializedViewNames = $this->materializedViewRemover->removeOlderThan((int)$daysOld);

        if ($materializedViewNames) {
            $symfonyStyle->success(
                sprintf(
                    '%d orphaned materialized views older than %d days have been successfully removed: %s',
                    count($materializedViewNames),
                    $daysOld,
                    implode(', ', $materializedViewNames)
                )
            );
        } else {
            $symfonyStyle->note(sprintf('There are no orphaned materialized views older than %d days', $daysOld));
        }

        return self::SUCCESS;
    }
}
