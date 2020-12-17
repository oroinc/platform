<?php

namespace  Oro\Bundle\NotificationBundle\Command;

use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends maintenance mass notification to a configured group of email or to enabled users if no emails were configured.
 */
class MassNotificationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:maintenance-notification';

    /** @var MassNotificationSender */
    private $massNotificationSender;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param MassNotificationSender $massNotificationSender
     * @param LoggerInterface $logger
     */
    public function __construct(MassNotificationSender $massNotificationSender, LoggerInterface $logger)
    {
        $this->massNotificationSender = $massNotificationSender;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setDescription(
                'Send mass notifications to all active application users ' .
                'or to the emails specified in the Recipients list under ' .
                'Maintenance Notification configuration settings'
            )
            ->addOption(
                'subject',
                null,
                InputOption::VALUE_OPTIONAL,
                'Subject of notification email. If empty, subject from the configured template is used'
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

        $sender = $senderEmail
            ? From::emailAddress($senderEmail, $senderName)
            : null;

        try {
            $count = $this->massNotificationSender->send($message, $subject, $sender);
        } catch (NotificationSendException $exception) {
            $this->logger->error('An error occurred while sending mass notification', ['exception' => $exception]);
            $output->writeln('An error occurred while sending mass notification');
            return;
        }

        $output->writeln(sprintf('%s notifications have been added to the queue', $count));
    }
}
