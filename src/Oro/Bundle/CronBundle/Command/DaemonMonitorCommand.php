<?php

namespace Oro\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Job\Daemon;
use Oro\Component\Log\OutputLogger;

class DaemonMonitorCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME  = 'oro:cron:daemon';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

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
        $logger  = new OutputLogger($output);
        $daemon  = $this->getContainer()->get('oro_cron.job_daemon');
        $stop    = (int)$input->getOption('stop');
        $start   = (int)$input->getOption('start');
        $restart = (int)$input->getOption('restart');

        if (($stop + $start + $restart) > 1) {
            throw new \RuntimeException('Please use only one option at a time');
        }

        if ($restart || $this->needToRestart($daemon)) {
            $stop  = true;
            $start = true;
        }

        if ($stop) {
            try {
                if ($daemon->stop()) {
                    $logger->info('Daemon was stopped');
                }
            } catch (\Exception $e) {
                $logger->info('Daemon already stopped');
            }
        }

        if ($start) {
            try {
                if ($daemon->run()) {
                    $logger->info('Daemon was started');
                }
            } catch (\Exception $e) {
                $logger->info('Daemon can`t be started');
            }
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
}
