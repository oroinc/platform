<?php

namespace Oro\Bundle\LoggerBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Email;

class LoggerEmailNotificationCommand extends ContainerAwareCommand
{
    const RECIPIENTS = 'recipients';
    const DISABLE = 'disable';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:logger:email-notification')
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

        /* @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.global');
        $recipientsConfigKey = Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS);
        if ($disable) {
            if (!$configManager->get($recipientsConfigKey)) {
                $io->text("Error logs notification already disabled.");

                return;
            }
            $configManager->reset($recipientsConfigKey);
            $io->text("Error logs notification successfully disabled.");
            $configManager->flush();

            return;
        }
        if ($recipients) {
            $errors = $this->validateRecipients($recipients);
            if (!empty($errors)) {
                $io->error($errors);

                return;
            }
            $configManager->set($recipientsConfigKey, $recipients);
            $io->text(["Error logs notification will be sent to listed email addresses:", $recipients]);

            $configManager->flush();

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
        $validator = $this->getContainer()->get('validator');
        $emails = explode(';', $recipients);
        $errors = [];
        foreach ($emails as $email) {
            $violations = $validator->validate($email, new Email);
            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s - %s', $email, $violation->getMessage());
                }
            }
        }

        return $errors;
    }
}
