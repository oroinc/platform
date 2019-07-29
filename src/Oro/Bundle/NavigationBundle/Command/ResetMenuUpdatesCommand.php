<?php

namespace Oro\Bundle\NavigationBundle\Command;

use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Resets menu updates depends on scope (organization/user).
 */
class ResetMenuUpdatesCommand extends Command
{
    protected static $defaultName = 'oro:navigation:menu:reset';

    /** @var UserManager */
    private $userManager;

    /** @var ScopeManager */
    private $scopeManager;

    /** @var MenuUpdateManager */
    private $menuUpdateManager;

    /** @var string */
    private $menuUpdateScopeType;

    public function __construct(
        UserManager $userManager,
        ScopeManager $scopeManager,
        MenuUpdateManager $menuUpdateManager,
        string $menuUpdateScopeType
    ) {
        parent::__construct();
        $this->userManager = $userManager;
        $this->scopeManager = $scopeManager;
        $this->menuUpdateManager = $menuUpdateManager;
        $this->menuUpdateScopeType = $menuUpdateScopeType;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->addOption(
                'user',
                'u',
                InputArgument::OPTIONAL,
                'Email of existing user'
            )
            ->addOption(
                'menu',
                'm',
                InputArgument::OPTIONAL,
                'Menu name to reset'
            )
            ->setDescription('Resets menu updates depends on scope (organization/user).')
            ->setHelp('If “user” param is not set - reset global scope, otherwise reset user scope.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $menu = $input->getOption('menu');
        $user = null;

        $email = $input->getOption('user');
        if ($email) {
            /** @var User $user */
            $user = $this->userManager->findUserByEmail($email);

            if (is_null($user)) {
                throw new \Exception(sprintf('User with email %s not exists.', $email));
            }
        }

        if (!$user && !$menu) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                '<question>WARNING! Menu for GLOBAL scope will be reset. Continue (y/n)?</question>',
                true
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return;
            }
        }

        $this->resetMenuUpdates($user, $menu);

        if ($user) {
            if ($menu) {
                $message = sprintf('The menu "%s" for user "%s" is successfully reset.', $menu, $email);
            } else {
                $message = sprintf('All menus for user "%s" is successfully reset.', $email);
            }
        } else {
            if ($menu) {
                $message = sprintf('The menu "%s" for global scope is successfully reset.', $menu);
            } else {
                $message = sprintf('All menus in global scope is successfully reset.');
            }
        }

        $output->writeln($message);
    }

    /**
     * @param User|null   $user
     * @param string|null $menuName
     */
    private function resetMenuUpdates($user = null, $menuName = null)
    {
        if (null !== $user) {
            $context = ['user' => $user];
        }

        $scope = $this->scopeManager->findOrCreate($this->menuUpdateScopeType, $context ?? []);

        $this->menuUpdateManager->deleteMenuUpdates($scope, $menuName);
    }
}
