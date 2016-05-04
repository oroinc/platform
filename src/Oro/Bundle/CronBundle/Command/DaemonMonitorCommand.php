<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Job\Daemon;
use Oro\Component\Log\OutputLogger;

class DaemonMonitorCommand extends ContainerAwareCommand
{
    const COMMAND_NAME  = 'oro:daemon';

    /** @var array */
    protected $repeatTime = [5, 10, 15, 25, 25];

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption(
                'start',
                'start',
                InputOption::VALUE_NONE,
                'If option exists Daemon will start'
            )
            ->addOption(
                'stop',
                'stop',
                InputOption::VALUE_NONE,
                'If option exists Daemon will stop'
            )
            ->addOption(
                'restart',
                'restart',
                InputOption::VALUE_NONE,
                'If option exists Daemon will restart'
            )
            ->setDescription('Monitor Daemon and restart it once a day if it has pid');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger       = new OutputLogger($output);
        $daemon       = $this->getContainer()->get('oro_cron.job_daemon');
        $em           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stop         = (int)$input->getOption('stop');
        $start        = (int)$input->getOption('start');
        $restart      = (int)$input->getOption('restart');
        $isClearQueue = false;

        if (($stop + $start + $restart) > 1) {
            throw new \RuntimeException('Please use only one option at a time');
        }

        if ($restart || $this->needToRestart($daemon)) {
            $stop  = true;
            $start = true;
        }

        foreach ($this->repeatTime as $time) {
            $jobQueue  = (int)$this->getJobInQueue($em);

            if ($jobQueue > 0) {
                $logger->info(sprintf('There is a %d job(s), script will wait %d seconds', $jobQueue, $time));
                sleep($time);
            } else {
                $isClearQueue = true;
                break;
            }
        }

        if ($stop && $isClearQueue) {
            $this->doStop($daemon, $logger);
        }

        if ($start && $isClearQueue) {
            $this->doStart($daemon, $logger);
        }
    }

    /**
     * @param Daemon $daemon
     * @param OutputLogger $logger
     */
    protected function doStop(Daemon $daemon, OutputLogger $logger)
    {
        try {
            if ($daemon->stop()) {
                $logger->info('Daemon was stopped');
            }
        } catch (\Exception $e) {
            $logger->info('Daemon already stopped');
        }
    }

    /**
     * @param Daemon $daemon
     * @param OutputLogger $logger
     */
    protected function doStart(Daemon $daemon, OutputLogger $logger)
    {
        try {
            if ($daemon->run()) {
                $logger->info('Daemon was started');
            }
        } catch (\Exception $e) {
            $logger->info('Daemon can`t be started');
        }
    }

    /**
     * @param Daemon $daemon
     *
     * @return bool
     */
    protected function needToRestart(Daemon $daemon)
    {
        if ($daemon->getPid()) {
            $interval = date_diff($daemon->getDateStart(), new \DateTime('now'));
            return ((int)$interval->format('%d')) >= 1;
        }

        return false;
    }

    /**
     * @param EntityManager $em
     *
     * @return string
     */
    protected function getJobInQueue(EntityManager $em)
    {
        $qb = $em->getRepository('JMSJobQueueBundle:Job')->createQueryBuilder('j');
        $qb->select($qb->expr()->count('j'));
        $qb->andWhere($qb->expr()->in('j.state', [Job::STATE_RUNNING, Job::STATE_NEW, Job::STATE_PENDING]));
        return $qb->getQuery()->getSingleScalarResult();
    }
}
