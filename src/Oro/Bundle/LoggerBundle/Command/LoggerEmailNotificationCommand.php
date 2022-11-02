<?php
declare(strict_types=1);

namespace Oro\Bundle\LoggerBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Updates logger email notification configuration.
 */
class LoggerEmailNotificationCommand extends Command
{
    protected static $defaultName = 'oro:logger:email-notification';

    private ValidatorInterface $validator;
    private ?ConfigManager $configManager;

    public function __construct(ValidatorInterface $validator, ?ConfigManager $configManager)
    {
        $this->validator = $validator;
        $this->configManager = $configManager;

        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->configManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable email notifications about logged errors')
            ->addOption('recipients', 'r', InputOption::VALUE_REQUIRED, 'Recipient email addresses separated by ;')
            ->setDescription('Updates logger email notification configuration.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates logger email notification configuration.

The <info>--disable</info> option can be used to disable email notifications about the logged errors:

  <info>php %command.full_name% --disable</info>

The <info>--recipients</info> option can be used to update the list of the recipients
that will receive email notifications about the logged errors:

  <info>php %command.full_name% --recipients=<recipients></info>
  <info>php %command.full_name% --recipients='email1@example.com;email2@example.com;emailN@example.com'</info>

HELP
            )
            ->addUsage('--disable')
            ->addUsage('--recipients=<recipients>')
            ->addUsage("--recipients='email1@example.com;email2@example.com;emailN@example.com'")
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $recipients = $input->getOption('recipients');

        $disable = $input->getOption('disable');

        $recipientsConfigKey = Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS);
        if ($disable) {
            if (!$this->configManager->get($recipientsConfigKey)) {
                $io->text("Error logs notification already disabled.");

                return 0;
            }
            $this->configManager->reset($recipientsConfigKey);
            $io->text("Error logs notification successfully disabled.");
            $this->configManager->flush();

            return 0;
        }
        if ($recipients) {
            $errors = $this->validateRecipients($recipients);
            if (!empty($errors)) {
                $io->error($errors);

                return 1;
            }
            $this->configManager->set($recipientsConfigKey, $recipients);
            $io->text(["Error logs notification will be sent to listed email addresses:", $recipients]);

            $this->configManager->flush();

            return 0;
        }

        $io->error('Please provide --recipients or add --disable flag to the command.');

        return 1;
    }

    protected function validateRecipients(string $recipients): array
    {
        $emails = explode(';', $recipients);
        $errors = [];
        foreach ($emails as $email) {
            $violations = $this->validator->validate($email, new Email);
            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s - %s', $email, $violation->getMessage());
                }
            }
        }

        return $errors;
    }
}
