<?php
declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates one-time link to impersonate a user.
 */
class ImpersonateUserCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:user:impersonate';

    private ManagerRegistry $registry;
    private UrlGeneratorInterface $urlGenerator;
    private ConfigManager $configManager;
    private UserManager $userManager;
    private DateTimeFormatterInterface $dateTimeFormatter;

    public function __construct(
        ManagerRegistry $registry,
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        UserManager $userManager,
        DateTimeFormatterInterface $dateTimeFormatter
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
        $this->userManager = $userManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the impersonated user')
            ->addOption(
                'lifetime',
                't',
                InputOption::VALUE_REQUIRED,
                'Token lifetime (seconds or strtotime format)',
                '1 day'
            )
            ->addOption('route', 'r', InputOption::VALUE_REQUIRED, 'Route of the generated URL', 'oro_default')
            ->addOption(
                'silent',
                'S',
                InputOption::VALUE_NONE,
                'Do not send email notification to the impersonated user'
            )
            ->setDescription('Generates one-time link to impersonate a user.')
            ->setHelp(
                <<<'HELP'
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

HELP
            )
            ->addUsage('--lifetime=<seconds> <username>')
            ->addUsage('--lifetime="15 minutes" <username>')
            ->addUsage('--lifetime="2 hours" <username>')
            ->addUsage('--route=<route> <username>')
            ->addUsage('--silent <username>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

            return $e->getCode() ?: 1;
        }

        return 0;
    }

    protected function createImpersonation(
        User $user,
        string $lifetime,
        bool $notifyImpersonatedUserByEmail
    ): Impersonation {
        $manager = $this->registry->getManagerForClass(Impersonation::class);

        if (is_numeric($lifetime)) {
            $lifetime .= ' sec';
        }

        $impersonation = new Impersonation();
        $impersonation->setUser($user);
        $impersonation->getExpireAt()->add(\DateInterval::createFromDateString($lifetime));
        $impersonation->setNotify($notifyImpersonatedUserByEmail);

        $manager->persist($impersonation);
        $manager->flush();

        return $impersonation;
    }

    protected function generateUrl(string $route, string $token): string
    {
        $applicationUrl = $this->configManager->get('oro_ui.application_url');

        return $applicationUrl . $this->urlGenerator->generate(
            $route,
            [
                ImpersonationAuthenticator::TOKEN_PARAMETER => $token,
            ]
        );
    }
}
