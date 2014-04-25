<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:user:update')
            ->setDescription('Update user.')
            ->addArgument(
                'user-name',
                InputArgument::OPTIONAL,
                'Username of user to update'
            )
            ->addOption('user-name', null, InputOption::VALUE_REQUIRED, 'User name')
            ->addOption('user-email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('user-firstname', null, InputOption::VALUE_REQUIRED, 'User first name')
            ->addOption('user-lastname', null, InputOption::VALUE_REQUIRED, 'User last name')
            ->addOption('user-password', null, InputOption::VALUE_REQUIRED, 'User password');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('user-name');
        $userManager = $this->getContainer()->get('oro_user.manager');
        $user = $userManager->findUserByUsername($username);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User "%s" not found.', $username));
        }

        $options = $input->getOptions();
        $user
            ->setUsername($options['user-name'])
            ->setEmail($options['user-email'])
            ->setFirstName($options['user-firstname'])
            ->setLastName($options['user-lastname'])
            ->setPlainPassword($options['user-password']);

        $userManager->updateUser($user);
    }
}
