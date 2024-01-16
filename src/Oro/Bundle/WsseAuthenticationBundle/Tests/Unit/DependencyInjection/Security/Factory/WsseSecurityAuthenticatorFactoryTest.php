<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\DependencyInjection\Security\Factory;

use Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory\WsseSecurityAuthenticatorFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WsseSecurityAuthenticatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPosition(): void
    {
        self::assertSame(0, $this->getWsseAuthenticatorFactory()->getPriority());
    }

    public function testGetKey(): void
    {
        self::assertSame('wsse', $this->getWsseAuthenticatorFactory()->getKey());
    }

    /**
     * @dataProvider createAuthenticatorDataProvider
     */
    public function testCreateAuthenticator(string $firewallName, array $configuration, string $userProviderId)
    {
        $wsseFactory = $this->getWsseAuthenticatorFactory();
        $container = new ContainerBuilder();
        $container->register('oro_wsse_authentication.security.core.authentication.wsse');
        $authenticatorId = $wsseFactory->createAuthenticator(
            $container,
            $firewallName,
            $configuration,
            $userProviderId
        );

        $expectedEncoderId = 'oro_wsse_authentication.hasher.' . $firewallName;
        self::assertTrue($container->hasDefinition($expectedEncoderId));
        $encoderDefinition = $container->getDefinition($expectedEncoderId);
        self::assertEquals(
            [
                'index_0' => $configuration['encoder']['algorithm'],
                'index_1' => $configuration['encoder']['encodeHashAsBase64'],
                'index_2' => $configuration['encoder']['iterations'],
            ],
            $encoderDefinition->getArguments()
        );
        $expectedNonceCacheId = 'oro_wsse_authentication.nonce_cache.' . $firewallName;
        self::assertTrue($container->hasDefinition($expectedNonceCacheId));
        $expectedEntriPointId = 'oro_wsse_authentication.security.http.entry_point.wsse.' . $firewallName;
        self::assertTrue($container->hasDefinition($expectedEntriPointId));
        $entriPointDefinition = $container->getDefinition($expectedEntriPointId);
        self::assertEquals(
            [
                'index_1' => $configuration['realm'],
                'index_2' => $configuration['profile'],
            ],
            $entriPointDefinition->getArguments()
        );

        $expectedAuthId = 'oro_wsse_authentication.security.core.authentication.authenticator.wsse.' . $firewallName;
        self::assertSame($expectedAuthId, $authenticatorId);
        self::assertTrue($container->hasDefinition($expectedAuthId));

        $authenticatorDefinition = $container->getDefinition($expectedAuthId);
        self::assertEquals(
            [
                'index_3' => new Reference('user_provider_test'),
                'index_4' => new Reference($expectedEntriPointId),
                'index_5' => $firewallName,
                'index_6' => new Reference($expectedEncoderId),
                'index_7' => new Reference($expectedNonceCacheId),
                'index_8' => $configuration['lifetime'],
            ],
            $authenticatorDefinition->getArguments()
        );
    }

    public function createAuthenticatorDataProvider(): array
    {
        return [
            'base configuration set' => [
                'firewallName' => 'test_firewall_1',
                'configuration' => [
                    'realm' => 'sample_realm',
                    'profile' => 'sample_profile',
                    'encoder' => [
                        'algorithm' => 'sha1',
                        'encodeHashAsBase64' => true,
                        'iterations' => 1,
                    ],
                    'lifetime' => 300,
                ],
                'userProviderId' => 'user_provider_test'
            ],
            'custom encoder configuration' => [
                'firewallName' => 'test_firewall_2',
                'configuration' => [
                    'realm' => 'sample_realm',
                    'profile' => 'sample_profile',
                    'encoder' => [
                        'algorithm' => 'sha512',
                        'encodeHashAsBase64' => true,
                        'iterations' => 5000,
                    ],
                    'lifetime' => 300,
                ],
                'userProviderId' => 'user_provider_test'
            ],
            'empty configuration' => [
                'firewallName' => 'test_firewall_2',
                'configuration' => [
                    'realm' => '',
                    'profile' => '',
                    'encoder' => [
                        'algorithm' => '',
                        'encodeHashAsBase64' => true,
                        'iterations' => 0,
                    ],
                    'lifetime' => 300,
                ],
                'userProviderId' => 'user_provider_test'
            ]
        ];
    }

    private function getWsseAuthenticatorFactory(): WsseSecurityAuthenticatorFactory
    {
        return new WsseSecurityAuthenticatorFactory();
    }
}
