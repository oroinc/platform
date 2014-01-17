<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

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
            ->setDescription('Runs synchronization for channel');
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
        /** @var ChannelRepository $repository */
        /** @var SyncProcessor $processor */
        $channelId  = $input->getOption('channel-id');
        $repository = $this->getService('doctrine.orm.entity_manager')->getRepository('OroIntegrationBundle:Channel');
        $logger     = new OutputLogger($output);
        $processor  = $this->getService(self::SYNC_PROCESSOR);
        $processor->getLoggerStrategy()->setLogger($logger);

        if ($this->isJobRunning($channelId)) {
            $logger->warning('Job already running. Terminating....');

            return 0;
        }

        if ($channelId) {
            $channel = $repository->getOrLoadById($channelId);
            if (!$channel) {
                throw new \InvalidArgumentException('Channel with given ID not found');
            }
            $channels = [$channel];
        } else {
            $channels = $repository->getConfiguredChannelsForSync();
        }

        /** @var Channel $channel */
        foreach ($channels as $channel) {
            try {
                $logger->notice(sprintf('Run sync for "%s" channel.', $channel->getName()));

                $processor->process($channel);
            } catch (\Exception $e) {
                $logger->critical($e->getMessage(), ['exception' => $e]);
                //process another channel even in case if exception thrown
                continue;
            }
        }

        $logger->notice('Completed');
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
        /** @var QueryBuilder $qb */
        $qb = $this->getService('doctrine.orm.entity_manager')
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
            )
                ->setParameter('channelIdType1', '%--channel-id=' . $channelId . '%')
                ->setParameter('channelIdType2', '%-c=' . $channelId . '%');
        }

        $running = $qb->getQuery()
            ->getSingleScalarResult();

        return $running > 1;
    }

    /**
     * Get service from DI container by id
     *
     * @param string $id
     *
     * @return object
     */
    protected function getService($id)
    {
        return $this->getContainer()->get($id);
    }
}
