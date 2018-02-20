<?php

namespace Oro\Bundle\NavigationBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearNavigationHistoryCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const CLEAN_BEFORE_PARAM = 'interval';
    const DEFAULT_INTERVAL = '1 day';

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
            ->setName('oro:navigation:history:clear')
            ->addOption(
                self::CLEAN_BEFORE_PARAM,
                'i',
                InputOption::VALUE_OPTIONAL,
                'All records taken earlier than now minus specified interval will be deleted. '.
                '(default: "' . self::DEFAULT_INTERVAL . '") Interval examples: "630 seconds", "P1D", etc. '. PHP_EOL .
                'See: <info>http://php.net/manual/en/datetime.formats.relative.php<info>'
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
            $cleanBefore->sub(
                \DateInterval::createFromDateString($interval)
            );

            if ($cleanBefore >= $now) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Value '%s' should be valid date interval",
                        $interval
                    )
                );
            }

            /** @var HistoryItemRepository $historyItemRepository */
            $historyItemRepository = $this->getContainer()->get('doctrine.orm.entity_manager')
                ->getRepository(NavigationHistoryItem::class);

            $deletedCnt = $historyItemRepository->clearHistoryItems($cleanBefore);

            $output->writeln(sprintf("'%d' items deleted from navigation history.", $deletedCnt));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
