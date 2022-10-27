<?php
declare(strict_types=1);

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates cron commands definitions stored in the database.
 */
class CronDefinitionsLoadCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:cron:definitions:load';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Updates cron commands definitions stored in the database.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates cron commands definitions stored in the database.

The previously loaded command definitions are removed from the database, and all command definitions
from <info>oro:cron</info> namespace that implement
<info>Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface</info>
are saved to the database.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Removing all previously loaded commands...</info>');
        $this->doctrine->getRepository('OroCronBundle:Schedule')
            ->createQueryBuilder('d')
            ->delete()
            ->getQuery()
            ->execute();

        $applicationCommands = $this->getApplication()->all('oro:cron');
        $em = $this->doctrine->getManagerForClass('OroCronBundle:Schedule');

        foreach ($applicationCommands as $name => $command) {
            if ($this === $command) {
                continue;
            }
            $output->write(sprintf('Processing command "<info>%s</info>": ', $name));
            if ($this->checkCommand($output, $command)) {
                $schedule = $this->createSchedule($output, $command, $name);
                $em->persist($schedule);
            }
        }

        $em->flush();

        return 0;
    }

    private function createSchedule(
        OutputInterface $output,
        CronCommandScheduleDefinitionInterface $command,
        string $name,
        array $arguments = []
    ): Schedule {
        $output->writeln('<comment>setting up schedule..</comment>');

        $schedule = new Schedule();
        $schedule
            ->setCommand($name)
            ->setDefinition($command->getDefaultDefinition())
            ->setArguments($arguments);

        return $schedule;
    }

    private function checkCommand(OutputInterface $output, Command $command): bool
    {
        if (!$command instanceof CronCommandScheduleDefinitionInterface) {
            $output->writeln(
                '<info>Skipping, the command does not implement CronCommandScheduleDefinitionInterface</info>'
            );

            return false;
        }

        if (!$command->getDefaultDefinition()) {
            $output->writeln('<error>no cron definition found, check command</error>');

            return false;
        }

        return true;
    }
}
