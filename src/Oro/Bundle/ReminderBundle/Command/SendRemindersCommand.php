<?php
declare(strict_types=1);

namespace Oro\Bundle\ReminderBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderSenderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends reminders that are due now.
 */
class SendRemindersCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:send-reminders';

    private ManagerRegistry $registry;
    private ReminderSenderInterface $sender;

    public function __construct(ManagerRegistry $registry, ReminderSenderInterface $sender)
    {
        $this->registry = $registry;
        $this->sender = $sender;

        parent::__construct();
    }

    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    public function isActive()
    {
        $count = $this->getReminderRepository()->countRemindersToSend();

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Sends reminders that are due now.')
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $reminders = $this->getReminderRepository()->findRemindersToSend();

        if (!$reminders) {
            $output->writeln('<info>No reminders to sent</info>');
            return;
        }

        $output->writeln(
            sprintf('<comment>Reminders to send:</comment> %d', count($reminders))
        );

        $em = $this->registry->getManager();
        try {
            $em->beginTransaction();

            $sentCount = $this->sendReminders($output, $reminders);

            $output->writeln(sprintf('<info>Reminders sent:</info> %d', $sentCount));

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Send reminders
     *
     * @param OutputInterface $output
     * @param Reminder[]      $reminders
     * @return int Count of sent reminders
     */
    protected function sendReminders(OutputInterface $output, array $reminders): int
    {
        $result = 0;

        foreach ($reminders as $reminder) {
            $this->sender->push($reminder);
        }

        $this->sender->send();

        foreach ($reminders as $reminder) {
            if (Reminder::STATE_SENT == $reminder->getState()) {
                $result += 1;
            }

            if (Reminder::STATE_FAIL == $reminder->getState()) {
                $exception = $reminder->getFailureException();
                $output->writeln(sprintf('<error>Failed to send reminder with id=%d</error>', $reminder->getId()));
                $output->writeln(sprintf('<info>%s</info>: %s', $exception['class'], $exception['message']));
            }
        }

        return $result;
    }

    protected function getReminderRepository(): ReminderRepository
    {
        return $this->registry->getRepository('OroReminderBundle:Reminder');
    }
}
