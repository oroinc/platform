<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\DependencyInjection\Security\Factory;

use Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory\WsseSecurityListenerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WsseSecurityListenerFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const ENCODER = 'oro_wsse_authentication.encoder.foo';
    private const NONCE_CACHE = 'oro_wsse_authentication.nonce_cache.foo';
    private const PROVIDER = 'oro_wsse_authentication.security.core.authentication.provider.wsse.foo';
    private const LISTENER = 'oro_wsse_authentication.security.http.firewall.wsse_authentication_listener.foo';
    private const ENTRY_POINT = 'oro_wsse_authentication.security.http.entry_point.wsse.foo';

    public function testGetPosition(): void
    {
        self::assertEquals('pre_auth', $this->getFactory()->getPosition());
    }

    private function getFactory(): WsseSecurityListenerFactory
    {
        return new WsseSecurityListenerFactory();
    }

    public function getKey(): void
    {
        self::assertEquals('wsse', $this->getFactory()->getKey());
    }

    public function testCreate(): void
    {
        $factory = $this->getFactory();

        $container = new ContainerBuilder();
        $container->register('oro_wsse_authentication.security.core.authentication.provider.wsse');

        $realm = 'sample_realm';
        $profile = 'sample_profile';
        $lifetime = 300;
        $date_format = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])'
            . '(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:'
            . '?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';

        $algorithm = 'sha1';
        $encodeHashAsBase64 = true;
        $iterations = 1;

        $encoder = [
            'algorithm' => $algorithm,
            'encodeHashAsBase64' => $encodeHashAsBase64,
            'iterations' => $iterations,
        ];

        [$authProviderId, $listenerId, $entryPointId] = $factory->create(
            $container,
            'foo',
            [
                'realm' => $realm,
                'profile' => $profile,
                'encoder' => $encoder,
                'lifetime' => $lifetime,
                'date_format' => $date_format,
            ],
            'user_provider',
            'entry_point'
        );

        self::assertTrue($container->hasDefinition(self::ENCODER));

        $definition = $container->getDefinition(self::ENCODER);
        self::assertEquals(
            [
                'index_0' => $algorithm,
                'index_1' => $encodeHashAsBase64,
                'index_2' => $iterations,
            ],
            $definition->getArguments()
        );

        self::assertTrue($container->hasDefinition(self::NONCE_CACHE));

        self::assertEquals(self::PROVIDER, $authProviderId);
        self::assertTrue($container->hasDefinition(self::PROVIDER));

        $definition = $container->getDefinition(self::PROVIDER);
        self::assertEquals(
            [
                'index_0' => new Reference('security.user_checker.foo'),
                'index_2' => new Reference('user_provider'),
                'index_3' => 'foo',
                'index_4' => new Reference(self::ENCODER),
                'index_5' => new Reference(self::NONCE_CACHE),
                'index_6' => $lifetime,
                'index_7' => $date_format,
            ],
            $definition->getArguments()
        );

        self::assertEquals(self::LISTENER, $listenerId);
        self::assertTrue($container->hasDefinition(self::LISTENER));

        $definition = $container->getDefinition(self::LISTENER);
        self::assertEquals(
            [
                'index_3' => new Reference($entryPointId),
                'index_4' => 'foo',
            ],
            $definition->getArguments()
        );

        self::assertEquals(self::ENTRY_POINT, $entryPointId);
        self::assertTrue($container->hasDefinition(self::ENTRY_POINT));

        $definition = $container->getDefinition(self::ENTRY_POINT);
        self::assertEquals(
            [
                'index_1' => $realm,
                'index_2' => $profile,
            ],
            $definition->getArguments()
        );
    }
}
