<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    const COMMAND_NAME   = 'oro:cron:channels:sync';
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
            ->setName(self::COMMAND_NAME)
            ->addOption(
                'channel-id',
                'c',
                InputOption::VALUE_OPTIONAL,
                'If option exists sync will be performed for given channel id'
            )
            ->setDescription('Runs synchronization for each configured channel');
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $channelId = $input->getOption('channel-id');
        $strategy  = $this->getContainer()
            ->get('oro_integration.logger.strategy');
        $strategy->setLogger(new OutputLogger($output));
        $repo = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroIntegrationBundle:Channel');

        if ($this->isJobRunning($channelId)) {
            $strategy->warning('Job already running. Terminating....');

            return 0;
        }

        if ($channelId) {
            $channel = $repo->getOrLoadById($channelId);
            if (!$channel) {
                throw new \InvalidArgumentException('Channel with given ID not found');
            }
            $channels = [$channel];
        } else {
            $channels = $repo->getConfiguredChannelsForSync();
        }


        /** @var Channel $channel */
        foreach ($channels as $channel) {
            try {
                $strategy->notice(sprintf('Run sync for "%s" channel.', $channel->getName()));

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
        $strategy->notice('Completed');
    }

    /**
     * Check is job running (from previous schedule)
     *
     * @param null|int $channelId
     *
     * @return bool
     */
    protected function isJobRunning($channelId)
    {
        $qb = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere('j.state=:stateName')
            ->setParameter('commandName', $this->getName())
            ->setParameter('stateName', Job::STATE_RUNNING);

        if ($channelId) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('j.args', ':channelIdType1'),
                    $qb->expr()->like('j.args', ':channelIdType2')
                )
            )->setParameter('channelIdType1', '%--channel-id=' . $channelId . '%')
             ->setParameter('channelIdType2', '%-c=' . $channelId . '%');
        }

        $running = $qb->getQuery()
            ->getSingleScalarResult();

        return $running > 1;
    }
}
