<?php
declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears old navigation history.
 */
class ClearNavigationHistoryCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    /** @var string */
    protected static $defaultName = 'oro:navigation:history:clear';

    private const DEFAULT_INTERVAL = '1 day';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        // 00:05 every day
        return '5 0 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Relative date/time')
            ->setDescription('Clears old navigation history.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears old navigation history.

  <info>php %command.full_name%</info>

The <info>--interval</info> option can be used to override the default time interval (1 day)
past which a navigation history item is considered old. It accepts any relative date/time
format recognized by PHP (<comment>https://php.net/manual/datetime.formats.relative.php</comment>):

  <info>php %command.full_name% --interval=<relative-date></info>
  <info>php %command.full_name% --interval="15 minutes"</info>
  <info>php %command.full_name% --interval="3 days"</info>

HELP
            )
            ->addUsage('--interval=<relative-date>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $interval = $input->getOption('interval') ?: self::DEFAULT_INTERVAL;

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cleanBefore = clone $now;

            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $dateInterval = @\DateInterval::createFromDateString($interval);
            if ($dateInterval instanceof \DateInterval) {
                $cleanBefore->sub($dateInterval);
            }

            if ($cleanBefore >= $now) {
                throw new \InvalidArgumentException(\sprintf("Value '%s' should be valid date interval", $interval));
            }

            /** @var HistoryItemRepository $historyItemRepository */
            $historyItemRepository = $this->doctrine->getRepository(NavigationHistoryItem::class);

            $deletedCnt = $historyItemRepository->clearHistoryItems($cleanBefore);

            $output->writeln(sprintf("'%d' items deleted from navigation history.", $deletedCnt));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return $e->getCode() ?: 1;
        }

        return 0;
    }
}
