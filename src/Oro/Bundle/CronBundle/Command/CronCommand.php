<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Oro\Bundle\CronBundle\Tools\CommandRunner;
use Psr\Log\LoggerInterface;
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
            ->setDescription('Cron commands launcher');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        // check for maintenance mode - do not run cron jobs if it is switched on
        if ($this->getContainer()->get('oro_platform.maintenance')->isOn()) {
            $message = 'System is in maintenance mode, aborting';
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $message));
            $logger->error($message);
            return;
        }

        $schedules = $this->getAllSchedules();

        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $cronExpression = $this->getCronHelper()->createCron($schedule->getDefinition());
            if ($cronExpression->isDue()) {
                /** @var CronCommandInterface $command */
                $command = $this->getApplication()->get($schedule->getCommand());

                // TODO: Should be properly refactored at BAP-13973
                if ($command instanceof CronCommandInterface && !$command->isActive()) {
                    $output->writeln(
                        'Skipping not enabled command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    continue;
                }

                // in case of synchronous cron command - run it in separate process
                if ($command instanceof SynchronousCommandInterface) {
                    $output->writeln(
                        'Running synchronous command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    CommandRunner::runCommand(
                        $schedule->getCommand(),
                        array_merge(
                            $schedule->getArguments(),
                            ['--env' => $this->getContainer()->getParameter('kernel.environment')]
                        )
                    );
                } else {
                    // in case of common cron command - send the MQ message that will run this command
                    $output->writeln(
                        'Scheduling run for command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    $this->getCommandRunner()->run(
                        $schedule->getCommand(),
                        $this->resolveOptions($schedule->getArguments())
                    );
                }
            } else {
                $output->writeln('Skipping not due command '.$schedule->getCommand(), OutputInterface::VERBOSITY_DEBUG);
            }
        }

        $output->writeln('All commands scheduled', OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * Convert command arguments to options. It needed for correctly pass this arguments into ArrayInput:
     * new ArrayInput(['name' => 'foo', '--bar' => 'foobar']);
     *
     * @param array $commandOptions
     * @return array
     */
    protected function resolveOptions(array $commandOptions)
    {
        $options = [];
        foreach ($commandOptions as $key => $option) {
            $params = explode('=', $option, 2);
            if (is_array($params) && count($params) === 2) {
                $options[$params[0]] = $params[1];
            } else {
                $options[$key] = $option;
            }
        }
        return $options;
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
