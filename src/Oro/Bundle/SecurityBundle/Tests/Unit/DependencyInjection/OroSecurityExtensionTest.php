<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSecurityExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('session.storage.options', ['cookie_lifetime' => 3600]);

        $extension = new OroSecurityExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'symfony_profiler_collection_of_voter_decisions' => ['value' => false, 'scope' => 'app'],
                        'system_check_symmetric_crypter_key' => ['value' => '', 'scope' => 'app']
                    ],
                ],
            ],
            $container->getExtensionConfig('oro_security')
        );

        $cookieTokenStorageDef = $container->getDefinition('oro_security.csrf.cookie_token_storage');
        self::assertEquals('auto', $cookieTokenStorageDef->getArgument(0));
        self::assertEquals('lax', $cookieTokenStorageDef->getArgument(2));

        self::assertSame([], $container->getParameter('oro_security.login_target_path_excludes'));

        $permissionsPolicyProviderDef = $container->getDefinition('oro_security.permission_policy_header_provider');
        self::assertFalse($permissionsPolicyProviderDef->getArgument(0));
        self::assertEquals([], $permissionsPolicyProviderDef->getArgument(1));

        self::assertEquals(
            ['cookie_lifetime' => 3600],
            $container->getParameter('oro_security.session.storage.options')
        );
    }
}
