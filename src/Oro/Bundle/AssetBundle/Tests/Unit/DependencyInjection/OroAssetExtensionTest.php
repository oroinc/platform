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
    }
}
