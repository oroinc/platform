<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class GenerateWSSEHeaderCommand extends ContainerAwareCommand
{
    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName('oro:wsse:generate-header');
        $this->setDescription('Generate X-WSSE HTTP header for a given user');
        $this->setDefinition(
            array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('organization', InputArgument::REQUIRED, 'The Organization'),
            )
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ContainerInterface $container */
        $container        = $this->getContainer();
        $username         = $input->getArgument('username');
        $organizationName = $input->getArgument('organization');
        $userManager      = $container->get('oro_user.manager');
        $user             = $userManager->findUserByUsername($username);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User "%s" does not exist', $username));
        }

        $organization = $container->get('oro_organization.organization_manager')->getEnabledUserOrganizationByName(
            $user,
            $organizationName,
            false
        );
        if (!$organization) {
            throw new \InvalidArgumentException(sprintf('Organization "%s" not found', $organizationName));
        }

        $userApi  = $userManager->getApi($user, $organization);
        if (!$userApi) {
            throw new \InvalidArgumentException(
                sprintf(
                    'User "%s" does not yet have an API key generated for organization "%s"',
                    $username,
                    $organizationName
                )
            );
        }

        $created = date('c');

        // http://stackoverflow.com/questions/18117695/how-to-calculate-wsse-nonce
        $prefix = gethostname();
        $nonce  = base64_encode(substr(md5(uniqid($prefix . '_', true)), 0, 16));
        $salt   = ''; // do not use real salt here, because API key already encrypted enough

        /** @var MessageDigestPasswordEncoder $encoder */
        $encoder        = $container->get('escape_wsse_authentication.encoder.wsse_secured');
        $passwordDigest = $encoder->encodePassword(
            sprintf(
                '%s%s%s',
                base64_decode($nonce),
                $created,
                $userApi->getApiKey()
            ),
            $salt
        );

        $output->writeln('<info>To use WSSE authentication add following headers to the request:</info>');
        $output->writeln('Authorization: WSSE profile="UsernameToken"');
        $output->writeln(
            sprintf(
                'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
                $username,
                $passwordDigest,
                $nonce,
                $created
            )
        );
        $output->writeln('');

        return 0;
    }
}
