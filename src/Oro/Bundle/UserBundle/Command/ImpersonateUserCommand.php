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

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription(
                'Generates one-time impersonation link for a given user.' . PHP_EOL .
                'Unused tokens expire after the specified time.'
            )
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
            ->addOption(
                'lifetime',
                't',
                InputOption::VALUE_REQUIRED,
                'Token lifetime (seconds or strtotime format)',
                '1 day'
            )
            ->addOption('route', 'r', InputOption::VALUE_REQUIRED, 'The route of generated URL', 'oro_default')
            ->addOption('silent', 'S', InputOption::VALUE_NONE, 'Do not send email to the impersonated user')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $user = $this->userManager->findUserByUsername($username);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" does not exists', $username));
        }

        if (!$user instanceof User) {
            // not a CRM user
            return 0;
        }

        $impersonation = $this->createImpersonation(
            $user,
            $input->getOption('lifetime'),
            !$input->getOption('silent')
        );
        $url = $this->generateUrl($input->getOption('route'), $impersonation->getToken());

        $output->writeln(
            sprintf(
                '<info>To login as user <comment>%s</comment> open the following URL ' .
                '(expires <comment>%s</comment>):</info>',
                $user->getUsername(),
                $this->dateTimeFormatter->format(
                    $impersonation->getExpireAt(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::FULL
                )
            )
        );
        $output->writeln($url);

        if (!$user->isEnabled()) {
            $output->writeln(
                '<comment>Warning: User is Disabled.' .
                ' You cannot impersonate them until enabled!</comment>'
            );
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE) {
            $output->writeln(
                sprintf(
                    '<comment>Warning: Auth status is \'%s\'.' .
                    ' You cannot impersonate them until it is set to \'Available\'!</comment>',
                    $user->getAuthStatus()->getName()
                )
            );
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
