<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Generate X-WSSE HTTP header for a given API key.
 */
class GenerateWsseHeaderCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:wsse:generate-header';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ManagerRegistry $registry
     * @param ContainerInterface $container
     */
    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->container = $container;
    }

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setDescription('Generate X-WSSE HTTP header for a given API key');
        $this->setDefinition([
            new InputArgument('apiKey', InputArgument::REQUIRED, 'User API Key.'),
            new InputOption(
                'firewall',
                null,
                InputArgument::OPTIONAL,
                'Firewall name.',
                $this->getDefaultSecurityFirewall()
            ),
        ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $apiKey = $input->getArgument('apiKey');
        /** @var UserApi $userApi */
        $userApi = $this->registry->getRepository($this->getApiKeyEntityClass())->findOneBy(
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
        $nonce = base64_encode(substr(md5(uniqid($prefix . '_', true)), 0, 16));
        $salt = ''; // do not use real salt here, because API key already encrypted enough

        $passwordDigest = $this->getPasswordEncoder($input->getOption('firewall'))->encodePassword(
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

    /**
     * @param string $firewallName
     *
     * @return PasswordEncoderInterface
     */
    private function getPasswordEncoder(string $firewallName): PasswordEncoderInterface
    {
        $serviceId = 'oro_wsse_authentication.encoder.' . $firewallName;
        if (!$this->container->has($serviceId)) {
            throw new \InvalidArgumentException(
                sprintf('WSSE password encoder for firewall "%s" is not defined', $firewallName)
            );
        }

        return $this->container->get($serviceId);
    }

    /**
     * @return string
     */
    protected function getApiKeyEntityClass(): string
    {
        return UserApi::class;
    }

    /**
     * @return string
     */
    protected function getDefaultSecurityFirewall(): string
    {
        return 'wsse_secured';
    }
}
