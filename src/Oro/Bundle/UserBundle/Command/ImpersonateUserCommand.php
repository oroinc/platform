<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;

class ImpersonateUserCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:user:impersonate')
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
        $user = $this->getContainer()->get('oro_user.manager')->findUserByUsername($username);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" does not exists', $username));
        }

        $impersonation = $this->createImpersonation($user, $input->getOption('lifetime'));
        $url = $this->generateUrl(
            $input->getOption('route'),
            $impersonation->getToken(),
            !$input->getOption('silent')
        );

        $datetimeFormatter = $this->getContainer()->get('oro_locale.formatter.date_time');
        $output->writeln(
            sprintf(
                '<info>To login as user <comment>%s</comment> open the following URL ' .
                '(expires <comment>%s</comment>):</info>',
                $user->getUsername(),
                $datetimeFormatter->format(
                    $impersonation->getExpireAt(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::FULL
                )
            )
        );
        $output->writeln($url);

        return 0;
    }

    /**
     * @param  User   $user
     * @param  string $lifetime
     * @return Impersonation
     */
    protected function createImpersonation(User $user, $lifetime)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        if (is_numeric($lifetime)) {
            $lifetime .= ' sec';
        }

        $impersonation = new Impersonation();
        $impersonation->setUser($user);
        $impersonation->getExpireAt()->add(\DateInterval::createFromDateString($lifetime));

        $manager->persist($impersonation);
        $manager->flush();

        return $impersonation;
    }

    /**
     * @param  string $route
     * @param  string $token
     * @param  bool   $notify
     * @return string
     */
    protected function generateUrl($route, $token, $notify)
    {
        $router = $this->getContainer()->get('router');
        $applicationUrl = $this->getContainer()->get('oro_config.manager')->get('oro_ui.application_url');

        return $applicationUrl . $router->generate(
            $route,
            [
                ImpersonationAuthenticator::TOKEN_PARAMETER => $token,
                ImpersonationAuthenticator::NOTIFY_PARAMETER => $notify,
            ]
        );
    }
}
