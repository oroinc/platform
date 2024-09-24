<?php

declare(strict_types=1);

namespace Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Security authentication for WSSE. Configures the container services required to use the authenticator
 */
class WsseSecurityAuthenticatorFactory implements AuthenticatorFactoryInterface
{
    private const ENTRY_POINT = 'oro_wsse_authentication.security.http.entry_point.wsse';
    private const NONCE_CACHE = 'oro_wsse_authentication.nonce_cache';
    private const HASHER = 'oro_wsse_authentication.hasher';
    private const AUTHENTICATOR = 'oro_wsse_authentication.security.core.authentication.authenticator.wsse';

    #[\Override]
    public function addConfiguration(NodeDefinition $builder): void
    {
        $builder
            ->children()
                ->scalarNode('provider')->end()
                ->scalarNode('realm')->defaultValue(null)->end()
                ->scalarNode('profile')->defaultValue('UsernameToken')->end()
                ->scalarNode('lifetime')->defaultValue(300)->end()
                ->arrayNode('encoder')
                    ->children()
                        ->scalarNode('algorithm')->end()
                        ->scalarNode('encodeHashAsBase64')->end()
                        ->scalarNode('iterations')->end()
                    ->end()
                ->end()
                ->scalarNode('nonce_cache_service_id')->defaultValue(null)->end()
            ->end();
    }

    #[\Override]
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string {
        $entryPointId = $this->createEntryPoint($container, $firewallName, $config);
        $hasherId = $this->createHasher($container, $firewallName, $config);
        $nonceCacheId = $this->createNonceCache($container, $firewallName, $config);
        $authenticatorId = self::AUTHENTICATOR . '.' . $firewallName;
        $container
            ->setDefinition($authenticatorId, new ChildDefinition(self::AUTHENTICATOR))
            ->replaceArgument(3, new Reference($userProviderId))
            ->replaceArgument(4, new Reference($entryPointId))
            ->replaceArgument(5, $firewallName)
            ->replaceArgument(6, new Reference($hasherId))
            ->replaceArgument(7, new Reference($nonceCacheId))
            ->replaceArgument(8, $config['lifetime']);

        return $authenticatorId;
    }

    #[\Override]
    public function getKey(): string
    {
        return 'wsse';
    }

    #[\Override]
    public function getPriority(): int
    {
        return 0;
    }

    private function createHasher(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $hasherId = self::HASHER . '.' . $firewallName;
        $container->setDefinition($hasherId, new ChildDefinition(self::HASHER));
        if (isset($config['encoder']['algorithm'])) {
            $container->getDefinition($hasherId)->replaceArgument(0, $config['encoder']['algorithm']);
        }
        if (isset($config['encoder']['encodeHashAsBase64'])) {
            $container->getDefinition($hasherId)->replaceArgument(1, $config['encoder']['encodeHashAsBase64']);
        }
        if (isset($config['encoder']['iterations'])) {
            $container->getDefinition($hasherId)->replaceArgument(2, $config['encoder']['iterations']);
        }
        $this->addHasherToServiceLocator($container, $hasherId);

        return $hasherId;
    }

    private function createNonceCache(ContainerBuilder $container, string $firewallName, array $config): string
    {
        if (isset($config['nonce_cache_service_id'])) {
            $nonceCacheId = $config['nonce_cache_service_id'];
        } else {
            $nonceCacheId = self::NONCE_CACHE . '.' . $firewallName;
            $container->setDefinition($nonceCacheId, new ChildDefinition(self::NONCE_CACHE));
        }
        $this->addNonceCacheToServiceLocator($container, $nonceCacheId);

        return $nonceCacheId;
    }

    private function addNonceCacheToServiceLocator(ContainerBuilder $container, string $nonceCacheServiceId): void
    {
        $nonceCacheServiceLocatorId = 'oro_wsse_authentication.service_locator.nonce_cache';
        $nonceCacheServiceLocator = $this->getLocatorForService($container, $nonceCacheServiceLocatorId);
        $encoders = $nonceCacheServiceLocator->getArgument(0);
        $encoders[$nonceCacheServiceId] = new Reference($nonceCacheServiceId);
        $nonceCacheServiceLocator->setArgument(0, $encoders);
    }

    private function getLocatorForService(ContainerBuilder $container, string $serviceLocatorId): Definition
    {
        if (!$container->hasDefinition($serviceLocatorId)) {
            $targetServiceLocator = new Definition(ServiceLocator::class, [[]]);
            $targetServiceLocator->addTag('container.service_locator');
            $container->setDefinition($serviceLocatorId, $targetServiceLocator);
        } else {
            $targetServiceLocator = $container->getDefinition($serviceLocatorId);
        }

        return $targetServiceLocator;
    }

    private function addHasherToServiceLocator(ContainerBuilder $container, string $hasherServiceId): void
    {
        $hasherServiceLocatorId = 'oro_wsse_authentication.service_locator.hasher';
        $hasherServiceLocator = $this->getLocatorForService($container, $hasherServiceLocatorId);
        $hashers = $hasherServiceLocator->getArgument(0);
        $hashers[$hasherServiceId] = new Reference($hasherServiceId);
        $hasherServiceLocator->setArgument(0, $hashers);
    }

    private function createEntryPoint(ContainerBuilder $container, string $firewallName, array $config): string
    {
        $entryPointId = self::ENTRY_POINT . '.' . $firewallName;
        $container
            ->setDefinition($entryPointId, new ChildDefinition(self::ENTRY_POINT))
            ->replaceArgument(1, $config['realm'])
            ->replaceArgument(2, $config['profile']);

        return $entryPointId;
    }
}
