<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Oro\Bundle\UserBundle\Entity\UserApi;

class GenerateWSSEHeaderCommand extends ContainerAwareCommand
{
    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName('oro:wsse:generate-header');
        $this->setDescription('Generate X-WSSE HTTP header for a given API key');
        $this->setDefinition(
            array(
                new InputArgument('apiKey', InputArgument::REQUIRED, 'User API Key'),
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
        $container = $this->getContainer();
        $apiKey = $input->getArgument('apiKey');
        /** @var UserApi $userApi */
        $userApi = $container->get('doctrine')->getRepository('OroUserBundle:UserApi')->findOneBy(
            ['apiKey' => $apiKey]
        );
        if (!$userApi) {
            throw new \InvalidArgumentException(
                sprintf(
                    'API key "%s" does not exists',
                    $apiKey
                )
            );
        }
        $user = $userApi->getUser();
        $organization = $userApi->getOrganization();
        if (!$organization->isEnabled()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Organization for API key "%s" is not active',
                    $apiKey
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
                $user->getUsername(),
                $passwordDigest,
                $nonce,
                $created
            )
        );
        $output->writeln('');

        return 0;
    }
}
