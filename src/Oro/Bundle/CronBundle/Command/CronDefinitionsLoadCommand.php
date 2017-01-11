<?php
namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronDefinitionsLoadCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:definitions:load')
            ->setDescription('Loads cron commands definitions from application to database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Removing all previously loaded commands...</info>');
        $this->getRepository('OroCronBundle:Schedule')->createQueryBuilder('d')->delete()->getQuery()->execute();

        $applicationCommands = $this->getApplication()->all('oro:cron');
        $em = $this->getEntityManager('OroCronBundle:Schedule');

        foreach ($applicationCommands as $name => $command) {
            $output->write(sprintf('Processing command "<info>%s</info>": ', $name));
            if ($this->checkCommand($output, $command)) {
                $schedule = $this->createSchedule($output, $command, $name);
                $em->persist($schedule);
            }
        }

        $em->flush();
    }

    /**
     * @param OutputInterface $output
     * @param CronCommandInterface $command
     * @param string $name
     * @param array $arguments
     *
     * @return Schedule
     */
    private function createSchedule(
        OutputInterface $output,
        CronCommandInterface $command,
        $name,
        array $arguments = []
    ) {
        $output->writeln('<comment>setting up schedule..</comment>');

        $schedule = new Schedule();
        $schedule
            ->setCommand($name)
            ->setDefinition($command->getDefaultDefinition())
            ->setArguments($arguments);

        return $schedule;
    }

    /**
     * @param OutputInterface $output
     * @param Command $command
     *
     * @return bool
     */
    private function checkCommand(OutputInterface $output, Command $command)
    {
        if (!$command instanceof CronCommandInterface) {
            $output->writeln(
                '<info>Skipping, the command does not implement CronCommandInterface</info>'
            );

            return false;
        }

        if (!$command->getDefaultDefinition()) {
            $output->writeln('<error>no cron definition found, check command</error>');

            return false;
        }

        return true;
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    private function getEntityManager($className)
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
}
