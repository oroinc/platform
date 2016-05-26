<?php

namespace  Oro\Bundle\NotificationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class MassNotificationCommand
 * Console command implementation
 *
 * @package Oro\Bundle\NotificationBundle\Command
 */
class MassNotificationCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:mass_notification:send';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                'subject',
                InputArgument::REQUIRED,
                'Set title for message'
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'Set body message'
            )
            ->addArgument(
                'sender',
                InputArgument::OPTIONAL,
                'Who send message'
            );
        $this->setDescription('Send mass notification for user');
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
        $output->writeln($this->getDescription());

        $helper = $this->getHelper('question');

        $subject = $input->getArgument('subject');
        $message = $input->getArgument('message');


        if (!$subject || empty($subject)) {
            $subject = $helper->ask($input, $output, $this->getHelperQuestions('Please enter a subject: '));
        }

        if (!$message || empty($message)) {
            $subject = $helper->ask($input, $output, $this->getHelperQuestions('Please enter a message: '));
        }

        $service = $this->getContainer()->get('oro_notification.mass_notification_processor');

        $service->send($subject, $message);

        $output->writeln('Completed');
    }

    /**
     * @param string        $question
     * @param null|string   $default
     * @return Question
     */
    protected function getHelperQuestions($question, $default = null)
    {
        $question = new Question($question, $default);

        $question->setValidator(function ($answer) {
            if(!$answer || empty(trim($answer))) {
                throw new \RuntimeException(
                    'This value should not be blank'
                );
            }
        });

        return $question;
    }
}
