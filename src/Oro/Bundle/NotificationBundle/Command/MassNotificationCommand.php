<?php

namespace  Oro\Bundle\NotificationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class MassNotificationCommand
 * Console command implementation
 *
 * @package Oro\Bundle\NotificationBundle\Command
 */
class MassNotificationCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:maintenance-notification';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Send mass notifications to all active application users ' .
                'or to the emails specified in the Recipients list under ' .
                'Maintenance Notification configuration settings'
            )
            ->addOption(
                'subject',
                null,
                InputOption::VALUE_OPTIONAL,
                'Subject of notification email. If emtpy, subject from the configured template is used'
            )
            ->addOption(
                'message',
                null,
                InputOption::VALUE_OPTIONAL,
                'Notification message to send'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the text file with message.'
            )
            ->addOption(
                'sender_name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Notification sender name'
            )
            ->addOption(
                'sender_email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Notification sender email'
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $subject     = $input->getOption('subject');
        $message     = $input->getOption('message');
        $senderName  = $input->getOption('sender_name');
        $senderEmail = $input->getOption('sender_email');
        $filePath    = $input->getOption('file');

        if ($filePath) {
            if (!is_readable($filePath)) {
                throw new \RuntimeException(
                    sprintf('Could not read %s file', $filePath)
                );
            }
            $message = file_get_contents($filePath);
        }

        $sender = $this->getContainer()->get('oro_notification.mass_notification_sender');

        $count = $sender->send($message, $subject, $senderEmail, $senderName);

        $output->writeln(sprintf('%s notifications have been added to the queue', $count));
    }
}
