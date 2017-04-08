<?php

namespace Oro\Bundle\CronBundle\Command;

use Psr\Log\LogLevel;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\CronBundle\Entity\Schedule;

class CronDefinitionsLoadCommand extends ContainerAwareCommand
{
    /** @var DeferredScheduler */
    protected $deferred;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:definitions:load')
            ->setDescription('Loads cron commands definitions from application to database.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deferred = $this->getDeferredScheduler($output);
        $this->removeOrphanedCronCommands($deferred);
        $this->loadCronCommands($deferred);
        $deferred->flush();

        $output->writeln('<info>The cron command definitions were successfully loaded.</info>');
    }

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    protected function removeOrphanedCronCommands(DeferredScheduler $deferredScheduler)
    {
        $schedulesForDelete = array_filter(
            $this->getRepository('OroCronBundle:Schedule')->findAll(),
            function (Schedule $schedule) {
                try {
                    $command = $this->getApplication()->get($schedule->getCommand());
                    if ($command instanceof CronCommandInterface &&
                        $command->getDefaultDefinition() !== $schedule->getDefinition() &&
                        preg_match('/^oro:cron/', $schedule->getCommand())
                    ) {
                        return true;
                    }
                } catch (CommandNotFoundException $e) {
                    return true;
                }

                return false;
            }
        );

        /** @var Schedule $schedule */
        foreach ($schedulesForDelete as $schedule) {
            $deferredScheduler->removeSchedule(
                $schedule->getCommand(),
                $schedule->getArguments(),
                $schedule->getDefinition()
            );
        }
    }

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    protected function loadCronCommands(DeferredScheduler $deferredScheduler)
    {
        $cronCommands = $this->getApplication()->all('oro:cron');
        foreach ($cronCommands as $command) {
            if ($command instanceof CronCommandInterface &&
                $command->getDefaultDefinition()
            ) {
                $deferredScheduler->addSchedule(
                    $command->getName(),
                    [],
                    $command->getDefaultDefinition()
                );
            }
        }
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
     * @param OutputInterface $output
     * @return DeferredScheduler
     */
    protected function getDeferredScheduler(OutputInterface $output)
    {
        if (null === $this->deferred) {
            $logger = new ConsoleLogger($output, [
                LogLevel::EMERGENCY => OutputInterface::VERBOSITY_QUIET,
                LogLevel::ALERT => OutputInterface::VERBOSITY_QUIET,
                LogLevel::CRITICAL => OutputInterface::VERBOSITY_QUIET,
                LogLevel::ERROR => OutputInterface::VERBOSITY_QUIET,
                LogLevel::WARNING => OutputInterface::VERBOSITY_QUIET,
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL,
            ]);

            $this->deferred = $this->getContainer()->get('oro_cron.deferred_scheduler');
            $this->deferred->setLogger($logger);
        }

        return $this->deferred;
    }
}
