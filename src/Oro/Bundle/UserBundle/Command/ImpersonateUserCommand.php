<?php

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates one-time impersonation link for a given user.
 */
class ImpersonateUserCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:user:impersonate';

    /** @var ManagerRegistry */
    private $registry;

    /** @var Router */
    private $router;

    /** @var ConfigManager */
    private $configManager;

    /** @var UserManager */
    private $userManager;

    /** @var DateTimeFormatterInterface */
    private $dateTimeFormatter;

    /**
     * ImpersonateUserCommand constructor.
     * @param ManagerRegistry $registry
     * @param Router $router
     * @param ConfigManager $configManager
     * @param UserManager $userManager
     * @param DateTimeFormatterInterface $dateTimeFormatter
     */
    public function __construct(
        ManagerRegistry $registry,
        Router $router,
        ConfigManager $configManager,
        UserManager $userManager,
        DateTimeFormatterInterface $dateTimeFormatter
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->router = $router;
        $this->configManager = $configManager;
        $this->userManager = $userManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    public function configure()
    {
        $this
            ->setDescription('Generates one-time link to impersonate a given user.')
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the impersonated user')
            ->addOption(
                'lifetime',
                't',
                InputOption::VALUE_REQUIRED,
                'Token lifetime (number of seconds or strtotime format)',
                '1 day'
            )
            ->addOption('route', 'r', InputOption::VALUE_REQUIRED, 'The route of the generated URL', 'oro_default')
            ->addOption(
                'silent',
                'S',
                InputOption::VALUE_NONE,
                'Do not send email notification to the impersonated user'
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command generates a one-time impersonation link for a given user:

  <info>%command.full_name% <username></info>

Unused tokens expire in 1 day, unless overridden with the <info>--lifetime</info> option:

  <info>%command.full_name% --lifetime=600 <username></info>
  <info>%command.full_name% --lifetime="10 minutes" <username></info>

The impersonated user will be notified by email when the impersonation link is used to log in.
Use the <info>--silent</info> option to avoid it:

  <info>%command.full_name% --silent <username></info>

Specify a custom target URL route via the <info>--route</info> option:

  <info>%command.full_name% --route=oro_user_profile_view <username></info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $username = $input->getArgument('username');
            $user = $this->userManager->findUserByUsername($username);

            if (!$user) {
                throw new \RuntimeException(\sprintf('User with username "%s" does not exist.', $username));
            }

            if (!$user instanceof User) {
                throw new \RuntimeException(
                    \sprintf('Unsupported user type, the user "%s" cannot be impersonated.', $username)
                );
            }

            $impersonation = $this->createImpersonation(
                $user,
                $input->getOption('lifetime'),
                !$input->getOption('silent')
            );
            $url = $this->generateUrl($input->getOption('route'), $impersonation->getToken());

            $io->text([
                \sprintf(
                    'To login as <info>%s</info> open the following URL (expires <info>%s</info>):',
                    $user->getUsername(),
                    $this->dateTimeFormatter->format(
                        $impersonation->getExpireAt(),
                        \IntlDateFormatter::MEDIUM,
                        \IntlDateFormatter::FULL
                    )
                ),
                $url
            ]);
            // Unfortunately we cannot use console hyperlinks because some terminal emulators don't support this syntax
            $io->newLine();

            if (!$user->isEnabled()) {
                $io->warning('User account is disabled. You will not be able to login as this user.');
            }

            if ($user->getAuthStatus() && $user->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE) {
                $io->warning([
                    \sprintf('The user\'s auth status is "%s".', $user->getAuthStatus()->getName()),
                    'You will not be able to login as this user until the auth status is changed to "Active".',
                ]);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return $e->getCode() ? $e->getCode() : 1;
        }

        return 0;
    }

    /**
     * @param  User   $user
     * @param  string $lifetime
     * @param  bool   $notify Enable email notification to impersonated user
     * @return Impersonation
     */
    protected function createImpersonation(User $user, $lifetime, $notify)
    {
        $manager = $this->registry->getManagerForClass(Impersonation::class);

        if (is_numeric($lifetime)) {
            $lifetime .= ' sec';
        }

        $impersonation = new Impersonation();
        $impersonation->setUser($user);
        $impersonation->getExpireAt()->add(\DateInterval::createFromDateString($lifetime));
        $impersonation->setNotify($notify);

        $manager->persist($impersonation);
        $manager->flush();

        return $impersonation;
    }

    /**
     * @param  string $route
     * @param  string $token
     * @return string
     */
    protected function generateUrl($route, $token)
    {
        $applicationUrl = $this->configManager->get('oro_ui.application_url');

        return $applicationUrl . $this->router->generate(
            $route,
            [
                ImpersonationAuthenticator::TOKEN_PARAMETER => $token,
            ]
        );
    }
}
