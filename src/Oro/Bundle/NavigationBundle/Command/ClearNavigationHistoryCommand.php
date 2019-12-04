<?php

namespace Oro\Bundle\NavigationBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears `oro_navigation_history` depending on datetime interval.
 */
class ClearNavigationHistoryCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:navigation:history:clear';

    private const CLEAN_BEFORE_PARAM = 'interval';
    private const DEFAULT_INTERVAL = '1 day';

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    /**
     * Run command at 00:05 every day.
     *
     * @return string
     */
    public function getDefaultDefinition()
    {
        return '5 0 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->addOption(
                self::CLEAN_BEFORE_PARAM,
                'i',
                InputOption::VALUE_OPTIONAL,
                'All records taken earlier than now minus specified interval will be deleted. '.
                '(default: "' . self::DEFAULT_INTERVAL . '") Interval examples: "630 seconds", "1 day", etc. ' . PHP_EOL
                . 'See: <info>http://php.net/manual/en/datetime.formats.relative.php<info>'
            )
            ->setDescription('Clears `oro_navigation_history` depending on datetime interval.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $interval = $input->getOption(self::CLEAN_BEFORE_PARAM) ?: self::DEFAULT_INTERVAL;

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cleanBefore = clone $now;

            // Starting from 7.1.28 and 7.2.17 PHP versions, a PHP Warning will be thrown if the value is not correct.
            // Disable error reporting to display own error without PHP Warning.
            $errorLevel = error_reporting();
            error_reporting(0);
            $cleanBefore->sub(
                \DateInterval::createFromDateString($interval)
            );
            error_reporting($errorLevel);

            if ($cleanBefore >= $now) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Value '%s' should be valid date interval",
                        $interval
                    )
                );
            }

            /** @var HistoryItemRepository $historyItemRepository */
            $historyItemRepository = $this->doctrine->getEntityManagerForClass(NavigationHistoryItem::class)
                ->getRepository(NavigationHistoryItem::class);

            $deletedCnt = $historyItemRepository->clearHistoryItems($cleanBefore);

            $output->writeln(sprintf("'%d' items deleted from navigation history.", $deletedCnt));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
