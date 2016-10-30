<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
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

        $daemon = $this->getContainer()->get('oro_cron.job_daemon');
        $skipCheckDaemon = $input->getOption('skipCheckDaemon');

        // check if daemon is running
        if (!$skipCheckDaemon && !$daemon->getPid()) {
            $output->writeln('', OutputInterface::VERBOSITY_DEBUG);

            if ($pid = $daemon->run()) {
                $output->write('Daemon process not found, running.. ', false, OutputInterface::VERBOSITY_DEBUG);
                $output->writeln(sprintf('<info>OK</info> (pid: %u)', $pid), OutputInterface::VERBOSITY_DEBUG);
            } else {
                $output->write('Daemon process not found, running.. ');
                $output->writeln('<error>failed</error>. Cron jobs can\'t be launched.');

                return;
            }
        }

        $schedules = $this->getAllSchedules();
        $em = $this->getEntityManager('JMSJobQueueBundle:Job');

        $jobs = $this->processCommands($output, $this->getApplication()->all('oro:cron'), $schedules);
        $jobs = array_merge($jobs, $this->processSchedules($output, $schedules));

        array_walk($jobs, [$em, 'persist']);

        $em->flush();

        $output->writeln('', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln('All commands finished', OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * This method accepts commands which implements CronCommandInterface and should have default definition.
     * All these commands must be used without parameters (just command name), so these commands should have default
     * values for any options (arguments) and they should use them.
     *
     * @param OutputInterface $output
     * @param array|Command[]|CronCommandInterface[] $commands
     * @param Collection|Schedule[] $schedules
     *
     * @return array|Job[]
     */
    protected function processCommands(OutputInterface $output, array $commands, Collection $schedules)
    {
        $jobs = [];
        $em = $this->getEntityManager('OroCronBundle:Schedule');

        foreach ($commands as $name => $command) {
            $output->write(
                sprintf('Processing command "<info>%s</info>": ', $name),
                false,
                OutputInterface::VERBOSITY_DEBUG
            );

            if ($this->skipCommand($output, $command)) {
                continue;
            }

            $matchedSchedules = $this->matchSchedules($schedules, $name);
            foreach ($matchedSchedules as $schedule) {
                $schedules->removeElement($schedule);
            }

            if (0 === count($matchedSchedules)) {
                $em->persist($this->createSchedule($output, $command, $name));

                continue;
            }

            $schedule = $matchedSchedules->first();
            $this->checkDefinition($command, $schedule);

            $maxCountConcurrentJobs = self::DEFAULT_MAX_COUNT_CONCURRENT_JOBS;
            if ($command instanceof CronCommandConcurrentJobsInterface) {
                $maxCountConcurrentJobs = $command->getMaxJobsCount();
            }

            if ($job = $this->createJob($output, $schedule, $maxCountConcurrentJobs)) {
                $jobs[] = $job;
            }
        }

        $em->flush();

        return $jobs;
    }

    /**
     * @param OutputInterface $output
     * @param Collection|Schedule[] $schedules
     *
     * @return array|Job[]
     */
    protected function processSchedules(OutputInterface $output, Collection $schedules)
    {
        $jobs = [];

        foreach ($schedules as $schedule) {
            if (!$this->getApplication()->has($schedule->getCommand())) {
                continue;
            }

            $arguments = $schedule->getArguments() ? ' ' . implode(' ', $schedule->getArguments()) : '';

            $output->write(
                sprintf('Processing command "<info>%s%s</info>": ', $schedule->getCommand(), $arguments),
                false,
                OutputInterface::VERBOSITY_DEBUG
            );

            if ($job = $this->createJob($output, $schedule)) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    /**
     * @param OutputInterface $output
     * @param Command $command
     *
     * @return bool
     */
    protected function skipCommand(OutputInterface $output, Command $command)
    {
        if (!$command instanceof CronCommandInterface) {
            $output->writeln(
                sprintf(
                    '<error>Unable to setup, command "<info>%s</info>" must be instance ' .
                    'of CronCommandInterface</error>',
                    $command->getName()
                )
            );

            return true;
        }

        if (!$command->getDefaultDefinition()) {
            $output->writeln(
                sprintf(
                    '<error>no cron definition found, check command "<info>%s</info>"</error>',
                    $command->getName()
                )
            );

            return true;
        }

        return false;
    }

    /**
     * @param Collection|Schedule[] $schedules
     * @param string $name
     * @param array $arguments
     *
     * @return Collection
     */
    protected function matchSchedules(Collection $schedules, $name, array $arguments = [])
    {
        return $schedules->filter(
            function (Schedule $schedule) use ($name, $arguments) {
                return $schedule->getCommand() === $name && $schedule->getArguments() == $arguments;
            }
        );
    }

    /**
     * @param OutputInterface $output
     * @param CronCommandInterface $command
     * @param string $name
     * @param array $arguments
     *
     * @return Schedule
     */
    protected function createSchedule(
        OutputInterface $output,
        CronCommandInterface $command,
        $name,
        array $arguments = []
    ) {
        $output->writeln(
            '<comment>new command found, setting up schedule..</comment>',
            OutputInterface::VERBOSITY_DEBUG
        );

        $schedule = new Schedule();
        $schedule
            ->setCommand($name)
            ->setDefinition($command->getDefaultDefinition())
            ->setArguments($arguments);

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
     * @param integer $maxJobsCount
     *
     * @return null|Job
     */
    protected function createJob(
        OutputInterface $output,
        Schedule $schedule,
        $maxJobsCount = self::DEFAULT_MAX_COUNT_CONCURRENT_JOBS
    ) {
        $cron = $this->getContainer()->get('oro_cron.helper.cron')->createCron($schedule->getDefinition());
        $arguments = array_values($schedule->getArguments());

        /**
         * @todo Add "Oro timezone" setting as parameter to isDue method
         */
        if ($cron->isDue()) {
            if (!$this->hasJobInQueue($schedule->getCommand(), $arguments)
                || $this->getJobsInQueueCount($schedule->getCommand(), $arguments) < $maxJobsCount
            ) {
                $job = new Job($schedule->getCommand(), $arguments);

                $output->writeln('<comment>added to job queue</comment>', OutputInterface::VERBOSITY_DEBUG);

                return $job;
            } else {
                $output->writeln('<comment>already exists in job queue</comment>', OutputInterface::VERBOSITY_DEBUG);
            }
        } else {
            $output->writeln('<comment>skipped</comment>', OutputInterface::VERBOSITY_DEBUG);
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    protected function hasJobInQueue($name, array $arguments)
    {
        return $this->getContainer()->get('oro_cron.job_manager')->hasJobInQueue($name, json_encode($arguments));
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getEntityManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }

    /**
     * @return ArrayCollection|Schedule[]
     */
    protected function getAllSchedules()
    {
        return new ArrayCollection($this->getRepository('OroCronBundle:Schedule')->findAll());
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return integer
     */
    protected function getJobsInQueueCount($name, array $arguments)
    {
        return $this->getContainer()->get('oro_cron.job_manager')->getJobsInQueueCount($name, json_encode($arguments));
    }
}
