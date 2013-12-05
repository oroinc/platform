<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;

/**
 * Class SyncCommand
 * Console command implementation
 *
 * @package Oro\Bundle\IntegrationBundle\Command
 */
class SyncCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const SYNC_PROCESSOR = 'oro_integration.sync.processor';

    /**
     * {@internaldoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:cron:channels:sync')
            ->setDescription('Runs synchronization for each configured channel');
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $strategy = $this->getContainer()
            ->get('oro_integration.logger.strategy');
        $strategy->setLogger(new OutputLogger($output));

        if ($this->isJobRunning()) {
            $strategy->warning('Job already running. Terminating....');

            return 0;
        }

        $channels = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroIntegrationBundle:Channel')
            ->getConfiguredChannelsForSync();

        /** @var Channel $channel */
        foreach ($channels as $channel) {
            try {
                $output->writeln(sprintf('Run sync for "%s" channel.', $channel->getName()));

                $this->getContainer()
                    ->get(self::SYNC_PROCESSOR)
                    ->process($channel);
            } catch (\Exception $e) {
                if ($output instanceof ConsoleOutputInterface) {
                    $this->getApplication()->renderException($e, $output->getErrorOutput());
                } else {
                    $this->getApplication()->renderException($e, $output);
                }

                //process another channel even in case if exception thrown
                continue;
            }
        }
        $strategy->info('Completed');
    }

    /**
     * Check is job running (from previous schedule)
     *
     * @return bool
     */
    protected function isJobRunning()
    {
        $qb = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j');

        $running = $qb
            ->select('count(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere($qb->expr()->in('j.state', [Job::STATE_RUNNING]))
            ->setParameter('commandName', $this->getName())
            ->getQuery()
            ->getSingleScalarResult();

        return $running > 1;
    }
}
