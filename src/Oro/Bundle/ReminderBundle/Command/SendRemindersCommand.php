<?php

namespace Oro\Bundle\ReminderBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderSenderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to send all reminders
 */
class SendRemindersCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:send-reminders';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ReminderSenderInterface */
    private $sender;

    /**
     * @param ManagerRegistry $registry
     * @param ReminderSenderInterface $sender
     */
    public function __construct(ManagerRegistry $registry, ReminderSenderInterface $sender)
    {
        $this->registry = $registry;
        $this->sender = $sender;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $count = $this->getReminderRepository()->countRemindersToSend();

        return ($count > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Send reminders');
    }

    /**
     * {@inheritdoc}
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

        $em = $this->getEntityManager();
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
    protected function sendReminders($output, array $reminders)
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

    /**
     * @return ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->registry->getRepository('OroReminderBundle:Reminder');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManager();
    }
}
