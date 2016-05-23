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
 * @package Oro\Bundle\NavigationBundle\Command
 */
class MassNotificationCommand extends ContainerAwareCommand
{
    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName('oro:mass_notification:send')
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

        $output->writeln('Completed');
    }

    public function getHelpMessage()
    {
        
    }
}