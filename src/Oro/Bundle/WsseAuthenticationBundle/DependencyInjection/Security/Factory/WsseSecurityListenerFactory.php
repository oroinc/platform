<?php

namespace Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Security authentication listener for WSSE. Configures the container services required to use the authentication
 * listener for WSSE authentication.
 */
class WsseSecurityListenerFactory implements SecurityFactoryInterface
{
    private const ENTRY_POINT = 'oro_wsse_authentication.security.http.entry_point.wsse';
    private const NONCE_CACHE = 'oro_wsse_authentication.nonce_cache';
    private const ENCODER = 'oro_wsse_authentication.encoder';
    private const AUTHENTICATION_PROVIDER = 'oro_wsse_authentication.security.core.authentication.provider.wsse';
    private const AUTHENTICATION_LISTENER
        = 'oro_wsse_authentication.security.http.firewall.wsse_authentication_listener';

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('provider')->end()
                ->scalarNode('realm')->defaultValue(null)->end()
                ->scalarNode('profile')->defaultValue('UsernameToken')->end()
                ->scalarNode('lifetime')->defaultValue(300)->end()
                ->scalarNode('date_format')->defaultValue(
                    '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?' .
                    '[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|2' .
                    '4\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/'
                )->end()
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

    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint): array
    {
        $providerId = $this->createAuthenticationProvider($container, $id, $config, $userProviderId);
        $entryPointId = $this->createEntryPoint($container, $id, $config);
        $listenerId = $this->createAuthenticationListener($container, $id, $entryPointId);

        return [$providerId, $listenerId, $entryPointId];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return 'wsse';
    }

    private function createEncoder(ContainerBuilder $container, string $id, array $config): string
    {
        $encoderId = self::ENCODER . '.' . $id;

        $container->setDefinition($encoderId, new ChildDefinition(self::ENCODER));

        if (isset($config['encoder']['algorithm'])) {
            $container->getDefinition($encoderId)->replaceArgument(0, $config['encoder']['algorithm']);
        }

        if (isset($config['encoder']['encodeHashAsBase64'])) {
            $container->getDefinition($encoderId)->replaceArgument(1, $config['encoder']['encodeHashAsBase64']);
        }

        if (isset($config['encoder']['iterations'])) {
            $container->getDefinition($encoderId)->replaceArgument(2, $config['encoder']['iterations']);
        }

        $this->addEncoderToServiceLocator($container, $encoderId);

        return $encoderId;
    }

    private function createNonceCache(ContainerBuilder $container, string $id, array $config): string
    {
        if (isset($config['nonce_cache_service_id'])) {
            $nonceCacheId = $config['nonce_cache_service_id'];
        } else {
            $nonceCacheId = self::NONCE_CACHE . '.' . $id;
            $container->setDefinition($nonceCacheId, new ChildDefinition(self::NONCE_CACHE));
        }

        $this->addNonceCacheToServiceLocator($container, $nonceCacheId);

        return $nonceCacheId;
    }

    private function addNonceCacheToServiceLocator(ContainerBuilder $container, string $nonceCacheServiceId): void
    {
        $nonceCacheServiceLocatorId = 'oro_wsse_authentication.service_locator.nonce_cache';
        if (!$container->hasDefinition($nonceCacheServiceLocatorId)) {
            $nonceCacheServiceLocator = new Definition(ServiceLocator::class, [[]]);
            $nonceCacheServiceLocator->addTag('container.service_locator');
            $container->setDefinition($nonceCacheServiceLocatorId, $nonceCacheServiceLocator);
        } else {
            $nonceCacheServiceLocator = $container->getDefinition($nonceCacheServiceLocatorId);
        }

        $encoders = $nonceCacheServiceLocator->getArgument(0);
        $encoders[$nonceCacheServiceId] = new Reference($nonceCacheServiceId);
        $nonceCacheServiceLocator->setArgument(0, $encoders);
    }

    private function createAuthenticationProvider(
        ContainerBuilder $container,
        string $id,
        array $config,
        string $userProviderId
    ): string {
        $encoderId = $this->createEncoder($container, $id, $config);
        $nonceCacheId = $this->createNonceCache($container, $id, $config);

        $providerId = self::AUTHENTICATION_PROVIDER . '.' . $id;

        $container
            ->setDefinition($providerId, new ChildDefinition(self::AUTHENTICATION_PROVIDER))
            ->replaceArgument(0, new Reference('security.user_checker.' . $id))
            ->replaceArgument(2, new Reference($userProviderId))
            ->replaceArgument(3, $id)
            ->replaceArgument(4, new Reference($encoderId))
            ->replaceArgument(5, new Reference($nonceCacheId))
            ->replaceArgument(6, $config['lifetime'])
            ->replaceArgument(7, $config['date_format']);

        return $providerId;
    }

    private function addEncoderToServiceLocator(ContainerBuilder $container, string $encoderServiceId): void
    {
        $encoderServiceLocatorId = 'oro_wsse_authentication.service_locator.encoder';
        if (!$container->hasDefinition($encoderServiceLocatorId)) {
            $encoderServiceLocator = new Definition(ServiceLocator::class, [[]]);
            $encoderServiceLocator->addTag('container.service_locator');
            $container->setDefinition($encoderServiceLocatorId, $encoderServiceLocator);
        } else {
            $encoderServiceLocator = $container->getDefinition($encoderServiceLocatorId);
        }

        $encoders = $encoderServiceLocator->getArgument(0);
        $encoders[$encoderServiceId] = new Reference($encoderServiceId);
        $encoderServiceLocator->setArgument(0, $encoders);
    }

    private function createEntryPoint(ContainerBuilder $container, string $id, array $config): string
    {
        $entryPointId = self::ENTRY_POINT . '.' . $id;

        $container
            ->setDefinition($entryPointId, new ChildDefinition(self::ENTRY_POINT))
            ->replaceArgument(1, $config['realm'])
            ->replaceArgument(2, $config['profile']);

        return $entryPointId;
    }

    private function createAuthenticationListener(ContainerBuilder $container, string $id, string $entryPointId): string
    {
        $listenerId = self::AUTHENTICATION_LISTENER . '.' . $id;

        $container
            ->setDefinition($listenerId, new ChildDefinition(self::AUTHENTICATION_LISTENER))
            ->replaceArgument(3, new Reference($entryPointId))
            ->replaceArgument(4, $id);

        return $listenerId;
    }
}
