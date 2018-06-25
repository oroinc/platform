<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\OroEmbeddedFormExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OroEmbeddedFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultConfiguration()
    {
        $container = new ContainerBuilder();
        $extension = new OroEmbeddedFormExtension();
        $extension->load([], $container);

        $this->assertEquals(
            '_embedded_form_sid',
            $container->getParameter(OroEmbeddedFormExtension::SESSION_ID_FIELD_NAME_PARAM)
        );
        $this->assertEquals(
            3600,
            $container->getParameter(OroEmbeddedFormExtension::CSRF_TOKEN_LIFETIME_PARAM)
        );
        $csrfTokenCacheDef = $container->getDefinition(OroEmbeddedFormExtension::DEFAULT_CSRF_TOKEN_CACHE_SERVICE_ID);
        $this->assertNotNull($csrfTokenCacheDef);
        $this->assertEquals(
            OroEmbeddedFormExtension::DEFAULT_CSRF_TOKEN_CACHE_CLASS,
            $csrfTokenCacheDef->getClass()
        );
        $this->assertEquals(
            [
                ['setNonceLifeTime', [3600]]
            ],
            $csrfTokenCacheDef->getMethodCalls()
        );

        $this->assertEquals(
            new Reference(OroEmbeddedFormExtension::DEFAULT_CSRF_TOKEN_CACHE_SERVICE_ID),
            $container->getDefinition(OroEmbeddedFormExtension::CSRF_TOKEN_STORAGE_SERVICE_ID)
                ->getArgument(0)
        );
    }

    public function testShouldOverrideSessionIdFieldName()
    {
        $container = new ContainerBuilder();
        $extension = new OroEmbeddedFormExtension();
        $extension->load(
            [
                ['session_id_field_name' => 'test']
            ],
            $container
        );

        $this->assertEquals(
            'test',
            $container->getParameter(OroEmbeddedFormExtension::SESSION_ID_FIELD_NAME_PARAM)
        );
    }

    public function testShouldOverrideCsrfTokenLifetime()
    {
        $container = new ContainerBuilder();
        $extension = new OroEmbeddedFormExtension();
        $extension->load(
            [
                ['csrf_token_lifetime' => 123]
            ],
            $container
        );

        $this->assertEquals(
            123,
            $container->getParameter(OroEmbeddedFormExtension::CSRF_TOKEN_LIFETIME_PARAM)
        );
        $this->assertEquals(
            [
                ['setNonceLifeTime', [123]]
            ],
            $container->getDefinition(OroEmbeddedFormExtension::DEFAULT_CSRF_TOKEN_CACHE_SERVICE_ID)
                ->getMethodCalls()
        );
    }

    public function testShouldOverrideCsrfTokenCacheService()
    {
        $container = new ContainerBuilder();
        $extension = new OroEmbeddedFormExtension();
        $extension->load(
            [
                ['csrf_token_cache_service_id' => 'test_service']
            ],
            $container
        );

        $this->assertEquals(
            new Reference('test_service'),
            $container->getDefinition(OroEmbeddedFormExtension::CSRF_TOKEN_STORAGE_SERVICE_ID)
                ->getArgument(0)
        );
    }
}
