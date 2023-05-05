<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\OroEmbeddedFormExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OroEmbeddedFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroEmbeddedFormExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        $this->assertEquals(
            '_embedded_form_sid',
            $container->getParameter('oro_embedded_form.session_id_field_name')
        );
        $this->assertEquals(
            3600,
            $container->getParameter('oro_embedded_form.csrf_token_lifetime')
        );

        $this->assertEquals(
            new Reference('oro_embedded_form.csrf_token_cache'),
            $container->getDefinition('oro_embedded_form.csrf_token_storage')->getArgument(0)
        );
    }

    public function testLoadShouldOverrideSessionIdFieldName(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            ['session_id_field_name' => 'test']
        ];

        $extension = new OroEmbeddedFormExtension();
        $extension->load($configs, $container);

        $this->assertEquals(
            'test',
            $container->getParameter('oro_embedded_form.session_id_field_name')
        );
    }

    public function testLoadShouldOverrideCsrfTokenLifetime(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            ['csrf_token_lifetime' => 123]
        ];

        $extension = new OroEmbeddedFormExtension();
        $extension->load($configs, $container);

        $this->assertEquals(
            123,
            $container->getParameter('oro_embedded_form.csrf_token_lifetime')
        );
    }

    public function testLoadShouldOverrideCsrfTokenCacheService(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            ['csrf_token_cache_service_id' => 'test_service']
        ];

        $extension = new OroEmbeddedFormExtension();
        $extension->load($configs, $container);

        $this->assertEquals(
            new Reference('test_service'),
            $container->getDefinition('oro_embedded_form.csrf_token_storage')->getArgument(0)
        );
    }

    public function testPrepend(): void
    {
        $securityConfig = [
            'clickjacking' => [
                'paths' => [
                    '^/.*' => 'DENY'
                ]
            ]
        ];

        $expectedConfig = [
            'clickjacking' => [
                'paths' => [
                    '/embedded-form/submit' => 'ALLOW',
                    '/embedded-form/success' => 'ALLOW',
                    '^/.*' => 'DENY'
                ]
            ]
        ];

        $container = $this->createMock(ExtendedContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('nelmio_security')
            ->willReturn([$securityConfig]);
        $container->expects($this->once())
            ->method('setExtensionConfig')
            ->with('nelmio_security', [$expectedConfig]);

        $extension = new OroEmbeddedFormExtension();
        $extension->prepend($container);
    }
}
