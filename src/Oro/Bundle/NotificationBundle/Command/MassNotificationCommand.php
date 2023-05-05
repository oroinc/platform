<?php
declare(strict_types=1);

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
 * Sends an email notification to the application users.
 */
class MassNotificationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:maintenance-notification';

    private MassNotificationSender $massNotificationSender;
    private LoggerInterface $logger;

    public function __construct(MassNotificationSender $massNotificationSender, LoggerInterface $logger)
    {
        $this->massNotificationSender = $massNotificationSender;
        $this->logger = $logger;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'Override the default subject')
            ->addOption('message', null, InputOption::VALUE_OPTIONAL, 'Notification message to send')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Path to the text file with message.')
            ->addOption('sender_name', null, InputOption::VALUE_OPTIONAL, 'Notification sender name')
            ->addOption('sender_email', null, InputOption::VALUE_OPTIONAL, 'Notification sender email')
            ->setDescription('Sends an email notification to the application users.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command sends an email notification to the recipients listed in 
<comment>System Configuration > General Setup > Email Configuration > Maintenance Notifications > Recipients</comment>.
If the recipient list in the system configuration is left empty, the notification will be sent
<options=bold>to all active application users</>.

  <info>php %command.full_name%</info>

The text of the message can be provide either as the value of the <info>--message</info> option
or it can be read from a text file specified in the <info>--file</info> option:

  <info>php %command.full_name% --message=<message-text></info>
  <info>php %command.full_name% --file=<path-to-text-file></info>

The <info>--subject</info> option can be used to override the default subject
provided by the configured email template:

  <info>php %command.full_name% --message=<message> --subject=<subject></info>

The <info>--sender_name</info> and <info>--sender_email</info> options can be used to override
the default name and email address of the sender:

  <info>php %command.full_name% --message=<message> --sender_name=<name> --sender_email=<email></info>

HELP
            )
            // @codingStandardsIgnoreEnd
            ->addUsage('--message=<message-text>')
            ->addUsage('--file=<path-to-text-file>')
            ->addUsage('--message=<message> --subject=<subject>')
            ->addUsage('--message=<message> --sender_name=<name> --sender_email=<email>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

            return 1;
        }

        $output->writeln(sprintf('%s notifications have been added to the queue', $count));

        return 0;
    }
}
