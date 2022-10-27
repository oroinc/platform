<?php
declare(strict_types=1);

namespace Oro\Bundle\WsseAuthenticationBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Flushes WSSE nonce cache.
 */
class DeleteNoncesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:wsse:nonces:delete';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure(): void
    {
        $this
            ->addOption('firewall', null, InputArgument::OPTIONAL, 'Firewall name', 'wsse_secured')
            ->setDescription('Flushes WSSE nonce cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command flushes WSSE nonce cache.

  <info>php %command.full_name%</info>

The <info>--firewall</info> option can be used to provide a name of the firewall:

  <info>php %command.full_name% --firewall=<firewall-name></info>

HELP
            )
            ->addUsage('--firewall=<firewall-name>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $firewall = $input->getOption('firewall');
            $this->getNonceCache($firewall)->clear();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return $e->getCode() ?: 1;
        }

        $io->success(\sprintf('Deleted nonce cache for %s firewall.', $firewall));
        return 0;
    }

    private function getNonceCache(string $firewallName): AdapterInterface
    {
        $serviceId = 'oro_wsse_authentication.nonce_cache.' . $firewallName;
        if (!$this->container->has($serviceId)) {
            throw new \InvalidArgumentException(
                \sprintf('WSSE nonce cache for firewall "%s" is not defined.', $firewallName)
            );
        }

        return $this->container->get($serviceId);
    }
}
