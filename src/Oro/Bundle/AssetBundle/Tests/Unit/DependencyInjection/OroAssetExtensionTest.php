<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\OroAssetExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAssetExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroAssetExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertFalse($container->getParameter('oro_asset.with_babel'));
        self::assertNotEmpty($container->getParameter('oro_asset.nodejs_path'));
        self::assertNotEmpty($container->getParameter('oro_asset.npm_path'));
        self::assertNull($container->getParameter('oro_asset.build_timeout'));
        self::assertNull($container->getParameter('oro_asset.npm_install_timeout'));
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
