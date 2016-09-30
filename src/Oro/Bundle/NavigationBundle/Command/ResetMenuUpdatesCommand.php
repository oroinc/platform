<?php

namespace Oro\Bundle\NavigationBundle\Command;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class ResetMenuUpdatesCommand
 * Console command implementation
 *
 * @package Oro\Bundle\NavigationBundle\Command
 */
class ResetMenuUpdatesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:navigation:menu:reset')
            ->addOption(
                'user',
                'u',
                InputArgument::OPTIONAL,
                'Email of existing user'
            )
            ->setDescription('Resets menu updates depends on scope (organization/user).')
            ->setHelp('If “user” param is not set - reset global scope, otherwise reset user scope.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $userMail = $input->getOption('user');

        if ($userMail) {
            $user = $this
                ->getContainer()
                ->get('oro_user.manager')
                ->findUserByEmail($userMail);

            if (is_null($user)) {
                throw new \Exception(sprintf('User with email %s not exists.', $userMail));
            }

            $this
                ->getContainer()
                ->get('oro_navigation.manager.menu_update_default')
                ->resetMenuUpdatesWithOwnershipType(MenuUpdate::OWNERSHIP_USER, $user->getId());

            $output->writeln(sprintf('The menu for the user %s is successfully reset.', $userMail));
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                '<question>WARNING! Menu for organization will be reset. Continue (y/n)?</question>',
                true
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return;
            }

            $this
                ->getContainer()
                ->get('oro_navigation.manager.menu_update_default')
                ->resetMenuUpdatesWithOwnershipType(MenuUpdate::OWNERSHIP_ORGANIZATION);

            $output->writeln(sprintf('The menu for the organization is successfully reset.', $userMail));
        }
    }
}
