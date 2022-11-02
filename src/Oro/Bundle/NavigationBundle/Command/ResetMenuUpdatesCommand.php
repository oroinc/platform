<?php
declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Command;

use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Resets menu updates.
 */
class ResetMenuUpdatesCommand extends Command
{
    protected static $defaultName = 'oro:navigation:menu:reset';

    private UserManager $userManager;
    private ScopeManager $scopeManager;
    private MenuUpdateManager $menuUpdateManager;
    private string $menuUpdateScopeType;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Email of existing user')
            ->addOption('menu', 'm', InputOption::VALUE_REQUIRED, 'Menu name to reset')
            ->setDescription('Resets menu updates.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command resets all menu updates.

  <info>php %command.full_name%</info>

The <info>--user</info> option can be used to reset menu updates for a specific user:

  <info>php %command.full_name% --user=<user-email></info>

The <info>--menu</info> option can be used to reset only a specific menu:

  <info>php %command.full_name% --menu=<menu-name></info>

Both options can be combined to further limit the scope being reset.

  <info>php %command.full_name% --user=<user-email> --menu=<menu-name></info>

HELP
            )
            ->addUsage('--menu=<menu-name>')
            ->addUsage('--user=<user-email>')
            ->addUsage('--user=<user-email> --menu=<menu-name>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

                return 1;
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

        return 0;
    }

    private function resetMenuUpdates(?User $user = null, ?string $menuName = null): void
    {
        if (null !== $user) {
            $context = ['user' => $user];
        }

        $scope = $this->scopeManager->findOrCreate($this->menuUpdateScopeType, $context ?? []);

        $this->menuUpdateManager->deleteMenuUpdates($scope, $menuName);
    }
}
