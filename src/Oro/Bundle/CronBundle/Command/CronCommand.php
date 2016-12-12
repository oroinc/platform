<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron')
            ->setDescription('Cron commands launcher')
        ;
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

        $schedules = $this->getAllSchedules();

        foreach ($schedules as $schedule) {
            $cronExpression = $this->getCronHelper()->createCron($schedule->getDefinition());
            if ($cronExpression->isDue()) {
                $output->writeln(
                    'Scheduling run for command '.$schedule->getCommand(),
                    OutputInterface::VERBOSITY_DEBUG
                );
                $this->getCommandRunner()->run($schedule->getCommand(), $schedule->getArguments());
            } else {
                $output->writeln('Skipping command '.$schedule->getCommand(), OutputInterface::VERBOSITY_DEBUG);
            }
        }

        $output->writeln('All commands scheduled', OutputInterface::VERBOSITY_DEBUG);
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
    private function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }

    /**
     * @return ArrayCollection|Schedule[]
     */
    private function getAllSchedules()
    {
        return new ArrayCollection($this->getRepository('OroCronBundle:Schedule')->findAll());
    }

    /**
     * @return CronHelper
     */
    private function getCronHelper()
    {
        return $this->getContainer()->get('oro_cron.helper.cron');
    }

    /**
     * @return CommandRunnerInterface
     */
    private function getCommandRunner()
    {
        return $this->getContainer()->get('oro_cron.async.command_runner');
    }
}
