<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\OroAssetExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAssetExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroAssetExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertFalse($container->getParameter('oro_asset.with_babel'));
        self::assertIsString($container->getParameter('oro_asset.nodejs_path'));
        self::assertIsString($container->getParameter('oro_asset.pnpm_path'));
        self::assertNull($container->getParameter('oro_asset.build_timeout'));
        self::assertNull($container->getParameter('oro_asset.pnpm_install_timeout'));
        self::assertSame(
            [
                'enable_hmr' => '%kernel.debug%',
                'host' => 'localhost',
                'port' => 8081,
                'https' => false
            ],
            $container->getParameter('oro_asset.webpack_dev_server_options')
        );
    }
}
