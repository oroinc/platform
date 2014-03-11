<?php

namespace Oro\Bundle\ReminderBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Command to send all reminders
 */
class SendRemindersCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@internaldoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:send-reminders')
            ->setDescription('Send reminders');
    }

    /**
     * {@internaldoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $reminders = $this->getReminderRepository()->findRemindersToSend();

        if (!$reminders) {
            $this->output->writeln('<info>No reminders to sent</info>');
            return;
        }

        $this->output->writeln(
            sprintf('<comment>Reminders to send:</comment> %d', count($reminders))
        );

        $em = $this->getEntityManager();
        try {
            $em->beginTransaction();

            $sentCount = $this->sendReminders($reminders);

            $this->output->writeln(sprintf('<info>Reminders sent:</info> %d', $sentCount));

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
     * @param Reminder[] $reminders
     * @return int Count of sent reminders
     */
    protected function sendReminders(array $reminders)
    {
        $result = 0;
        $sender = $this->getReminderSender();

        foreach ($reminders as $reminder) {
            $sender->send($reminder);

            if (Reminder::STATE_SENT == $reminder->getState()) {
                $result += 1;
            }

            if (Reminder::STATE_FAIL == $reminder->getState()) {
                $this->output->writeln(
                    sprintf('<error>Failed to send:</error> #%d', $reminder->getId())
                );
            }
        }

        return $result;
    }

    /**
     * @return ReminderSender
     */
    protected function getReminderSender()
    {
        return $this->getContainer()->get('oro_reminder.sender');
    }

    /**
     * @return ReminderRepository
     */
    protected function getReminderRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroReminderBundle:Reminder');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
