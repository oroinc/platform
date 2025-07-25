<?php

declare(strict_types=1);

namespace Oro\Bundle\ReminderBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderSenderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends reminders that are due now.
 */
#[AsCommand(
    name: 'oro:cron:send-reminders',
    description: 'Sends reminders that are due now.'
)]
class SendRemindersCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    private ManagerRegistry $doctrine;
    private ReminderSenderInterface $sender;

    public function __construct(ManagerRegistry $doctrine, ReminderSenderInterface $sender)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->sender = $sender;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }

    #[\Override]
    public function isActive(): bool
    {
        $count = $this->getReminderRepository()->countRemindersToSend();

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command sends reminders that are due now.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reminders = $this->getReminderRepository()->findRemindersToSend();
        if (!$reminders) {
            $output->writeln('<info>No reminders to sent</info>');
            return Command::SUCCESS;
        }

        $output->writeln(
            sprintf('<comment>Reminders to send:</comment> %d', count($reminders))
        );

        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $sentCount = $this->sendReminders($output, $reminders);

            $output->writeln(sprintf('<info>Reminders sent:</info> %d', $sentCount));

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return Command::SUCCESS;
    }

    private function sendReminders(OutputInterface $output, array $reminders): int
    {
        foreach ($reminders as $reminder) {
            $this->sender->push($reminder);
        }
        $this->sender->send();

        $result = 0;
        /** @var Reminder $reminder */
        foreach ($reminders as $reminder) {
            if (Reminder::STATE_SENT === $reminder->getState()) {
                $result++;
            }

            if (Reminder::STATE_FAIL === $reminder->getState()) {
                $exception = $reminder->getFailureException();
                $output->writeln(sprintf('<error>Failed to send reminder with id=%d</error>', $reminder->getId()));
                $output->writeln(sprintf('<info>%s</info>: %s', $exception['class'], $exception['message']));
            }
        }

        return $result;
    }

    private function getReminderRepository(): ReminderRepository
    {
        return $this->doctrine->getRepository(Reminder::class);
    }
}
