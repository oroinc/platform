<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Command;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates a user.
 */
#[AsCommand(
    name: 'oro:user:update',
    description: 'Updates a user.'
)]
class UpdateUserCommand extends CreateUserCommand
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument('user-name', InputArgument::REQUIRED, 'Username')
            ->addOption('user-name', null, InputOption::VALUE_REQUIRED, 'New username')
            ->addOption('user-email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('user-firstname', null, InputOption::VALUE_REQUIRED, 'First name')
            ->addOption('user-lastname', null, InputOption::VALUE_REQUIRED, 'Last name')
            ->addOption('user-password', null, InputOption::VALUE_REQUIRED, 'Password')
            ->addOption(
                'user-organizations',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Organizations'
            )
            // phpcs:disable
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates user details.

  <info>php %command.full_name%</info>

The <info>--user-email</info>, <info>--user-firstname</info>, <info>--user-lastname</info> options can be used to update details of the specified user:

  <info>php %command.full_name% --user-email=<email> --user-firstname=<firstname> --user-lastname=<lastname> <username></info>

The <info>--user-password</info> option can be used to update the user password:

  <info>php %command.full_name% --user-password=<password> <username></info>

The <info>--user-name</info> option can be used to change the username.
The provided value becomes the new username:

  <info>php %command.full_name% --user-name=<new-username> <old-username></info>

HELP
            )
            ->addUsage('--user-email=<email> --user-firstname=<firstname> --user-lastname=<lastname> <username>')
            ->addUsage('--user-password=<password> <username>')
            ->addUsage('--user-name=<new-username> <old-username>')
            // phpcs:enable
        ;

        if ($this->featureChecker->isFeatureEnabled('user_login_password')) {
            $this->addOption('user-password', null, InputOption::VALUE_REQUIRED, 'Password');
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('user-name');
        /** @var User $user */
        $user     = $this->userManager->findUserByUsername($username);
        $options  = $input->getOptions();

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User "%s" not found.', $username));
        }

        try {
            $this->updateUser($user, $options);
        } catch (InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());

            return $exception->getCode() ?: 1;
        }

        return Command::SUCCESS;
    }
}
