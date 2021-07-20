<?php

namespace Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces placeholder arguments of WSSE nonce cache services definitions with real arguments.
 */
class WsseNonceCachePass implements CompilerPassInterface
{
    private const NONCE_CACHE = 'oro_wsse_authentication.nonce_cache';
    private const DEFAULT_LIFETIME = 300;

    /** @var string */
    private $wsseKey;

    public function __construct(string $wsseKey)
    {
        $this->wsseKey = $wsseKey;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $securityConfigs = $container->getExtensionConfig('security');
        if (empty($securityConfigs[0]['firewalls'])) {
            return;
        }

        $defaultNonceCacheArguments = $container->getDefinition(self::NONCE_CACHE)->getArguments();

        foreach ($securityConfigs[0]['firewalls'] as $firewallName => $config) {
            if (!isset($config[$this->wsseKey])) {
                continue;
            }

            $nonceCacheDef = $container->getDefinition(self::NONCE_CACHE . '.' . $firewallName);
            foreach ($defaultNonceCacheArguments as $index => $argument) {
                if ($argument === '<lifetime>') {
                    $nonceCacheDef
                        ->replaceArgument($index, $config[$this->wsseKey]['lifetime'] ?? self::DEFAULT_LIFETIME);
                } elseif ($argument === '<namespace>') {
                    $nonceCacheDef->replaceArgument($index, 'wsse_nonce_' . $firewallName);
                }
            }
        }
    }
}
