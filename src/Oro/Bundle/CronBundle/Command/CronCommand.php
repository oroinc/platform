<?php

namespace Oro\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\CronBundle\Entity\Schedule;

class CronCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:cron';
    const DEFAULT_MAX_COUNT_CONCURRENT_JOBS = 1;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Cron commands launcher')
            ->addOption(
                'skipCheckDaemon',
                null,
                InputOption::VALUE_NONE,
                'Skipping check daemon is running'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check for maintenance mode - do not run cron jobs if it is switched on
        if ($this->getContainer()->get('oro_platform.maintenance')->isOn()) {
            $output->writeln('');
            $output->writeln('<error>System is in maintenance mode, aborting</error>');

            return;
        }

        $commands = $this->getApplication()->all('oro:cron');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $daemon = $this->getContainer()->get('oro_cron.job_daemon');
        $schedules = $em->getRepository('OroCronBundle:Schedule')->findAll();
        $skipCheckDaemon = $input->getOption('skipCheckDaemon');

        // check if daemon is running
        if (!$skipCheckDaemon && !$daemon->getPid()) {
            $output->writeln('');
            $output->write('Daemon process not found, running.. ');

            if ($pid = $daemon->run()) {
                $output->writeln(sprintf('<info>OK</info> (pid: %u)', $pid));
            } else {
                $output->writeln('<error>failed</error>. Cron jobs can\'t be launched.');

                return;
            }
        }

        foreach ($commands as $name => $command) {
            $output->write(sprintf('Processing command "<info>%s</info>": ', $name));

            if ($this->skipCommand($command, $output)) {
                continue;
            }

            $schedule = $this->getSchedule($schedules, $name);
            if (0 === count($schedule)) {
                $schedule = $this->createSchedule($command, $name, $output);
                $em->persist($schedule);
                continue;
            }

            $schedule = current($schedule);
            $this->checkDefinition($command, $schedule);
            $maxCountConcurrentJobs = self::DEFAULT_MAX_COUNT_CONCURRENT_JOBS;
            if ($command instanceof CronCommandConcurrentJobsInterface) {
                $maxCountConcurrentJobs = $command->getMaxJobsCount();
            }

            $job = $this->createJob($output, $schedule, $name, $maxCountConcurrentJobs);

            if ($job) {
                $em->persist($job);
            }
        }

        $em->flush();

        $output->writeln('');
        $output->writeln('All commands finished');
    }

    /**
     * @param CronCommandInterface $command
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function skipCommand(CronCommandInterface $command, OutputInterface $output)
    {
        if (!$command instanceof CronCommandInterface) {
            $output->writeln(
                '<error>Unable to setup, command must be instance of CronCommandInterface</error>'
            );

            return true;
        }

        if (!$command->getDefaultDefinition()) {
            $output->writeln('<error>no cron definition found, check command</error>');

            return true;
        }

        return false;
    }

    /**
     * @param Schedule[] $schedules
     * @param string $name
     *
     * @return array
     */
    protected function getSchedule(array $schedules, $name)
    {
        $schedule = array_filter(
            $schedules,
            function ($element) use ($name) {
                /** @var Schedule $element */
                return $element->getCommand() == $name;
            }
        );

        return $schedule;
    }

    /**
     * @param CronCommandInterface $command
     * @param string $name
     * @param OutputInterface $output
     *
     * @return Schedule
     */
    protected function createSchedule(CronCommandInterface $command, $name, OutputInterface $output)
    {
        $output->writeln('<comment>new command found, setting up schedule..</comment>');

        $schedule = new Schedule();
        $schedule
            ->setCommand($name)
            ->setDefinition($command->getDefaultDefinition());

        return $schedule;
    }

    /**
     * @param CronCommandInterface $command
     * @param Schedule $schedule
     */
    protected function checkDefinition(CronCommandInterface $command, Schedule $schedule)
    {
        $defaultDefinition = $command->getDefaultDefinition();
        if ($schedule->getDefinition() !== $defaultDefinition) {
            $schedule->setDefinition($defaultDefinition);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Schedule $schedule
     * @param string $name
     * @param integer $maxJobsCount
     *
     * @return bool|Job
     */
    protected function createJob(
        OutputInterface $output,
        Schedule $schedule,
        $name,
        $maxJobsCount = self::DEFAULT_MAX_COUNT_CONCURRENT_JOBS
    ) {
        $cronHelper = $this->getContainer()->get('oro_cron.helper.cron');
        $cron = $cronHelper->createCron($schedule->getDefinition());
        /**
         * @todo Add "Oro timezone" setting as parameter to isDue method
         */
        if ($cron->isDue()) {
            if (!$this->hasJobInQueue($schedule->getCommand())
                || $this->getJobsInQueueCount($schedule->getCommand()) < $maxJobsCount
            ) {
                $job = new Job($name);

                $output->writeln('<comment>added to job queue</comment>');

                return $job;
            } else {
                $output->writeln('<comment>already exists in job queue</comment>');
            }
        } else {
            $output->writeln('<comment>skipped</comment>');
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function hasJobInQueue($name)
    {
        $jobManager = $this->getContainer()->get('oro_cron.job_manager');

        return $jobManager->hasJobInQueue($name, '[]');
    }

    /**
     * @param string $name
     *
     * @return integer
     */
    protected function getJobsInQueueCount($name)
    {
        return $this->getContainer()->get('oro_cron.job_manager')->getJobsInQueueCount($name, '[]');
    }
}
