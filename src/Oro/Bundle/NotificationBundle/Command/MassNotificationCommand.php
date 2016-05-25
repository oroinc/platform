<?php

namespace  Oro\Bundle\NotificationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
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
    const COMMAND_NAME = 'oro:mass_notification:send';

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                'title',
                InputArgument::REQUIRED,
                'Set title for message'
            )
            ->addArgument(
                'body',
                InputArgument::REQUIRED,
                'Set body message'
            )
            ->addArgument(
                'from',
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
        
        $sender = $this->getContainer()->get('oro_notification.mass_notification_processor');

        $sender->send($input->getArgument('title'), $input->getArgument('body'));

        $output->writeln('Completed');
    }
}
