<?php

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
 * Update logger email notification configuration
 */
class LoggerEmailNotificationCommand extends Command
{
    protected static $defaultName = 'oro:logger:email-notification';

    private const RECIPIENTS = 'recipients';
    private const DISABLE = 'disable';

    /** @var ValidatorInterface */
    private $validator;

    /** @var ConfigManager|null */
    private $configManager;

    /**
     * @param ValidatorInterface $validator
     * @param ConfigManager $configManager
     */
    public function __construct(ValidatorInterface $validator, ?ConfigManager $configManager)
    {
        $this->validator = $validator;
        $this->configManager = $configManager;
        
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return (bool) $this->configManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                self::DISABLE,
                null,
                InputOption::VALUE_NONE,
                'Add this option to disable email notifications about errors in log.'
            )
            ->addOption(
                self::RECIPIENTS,
                'r',
                InputOption::VALUE_REQUIRED,
                'To send notifications about errors in log write email addresses separated by semicolon (;).'
            )
            ->setDescription('Update logger email notification configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $user = null;
        $recipients = $input->getOption(self::RECIPIENTS);

        $disable = $input->getOption(self::DISABLE);

        $recipientsConfigKey = Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS);
        if ($disable) {
            if (!$this->configManager->get($recipientsConfigKey)) {
                $io->text("Error logs notification already disabled.");

                return;
            }
            $this->configManager->reset($recipientsConfigKey);
            $io->text("Error logs notification successfully disabled.");
            $this->configManager->flush();

            return;
        }
        if ($recipients) {
            $errors = $this->validateRecipients($recipients);
            if (!empty($errors)) {
                $io->error($errors);

                return;
            }
            $this->configManager->set($recipientsConfigKey, $recipients);
            $io->text(["Error logs notification will be sent to listed email addresses:", $recipients]);

            $this->configManager->flush();

            return;
        }

        $io->error('Please provide --recipients or add --disable flag to the command.');
    }

    /**
     * @param string $recipients
     * @return array
     */
    protected function validateRecipients($recipients)
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
