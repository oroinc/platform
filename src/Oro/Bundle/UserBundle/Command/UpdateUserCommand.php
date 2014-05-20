<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Exception\InvalidArgumentException;

class UpdateUserCommand extends CreateUserCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:user:update')
            ->setDescription('Update user.')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'Username of user to update'
            )
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'User name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'User first name')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'User last name')
            ->addOption('plain-password', null, InputOption::VALUE_REQUIRED, 'User password');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $user     = $this->getUserManager()->findUserByUsername($username);
        $options  = $input->getOptions();

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User "%s" not found.', $username));
        }

        try {
            $this->updateUser($user, $options);
        } catch (InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());
        }
    }
}
