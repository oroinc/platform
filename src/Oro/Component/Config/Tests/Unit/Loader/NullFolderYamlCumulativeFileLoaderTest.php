<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Bundle\FrontendBundle\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\NullFolderYamlCumulativeFileLoader;
use PHPUnit\Framework\TestCase;

class NullFolderYamlCumulativeFileLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $relativePath = '../../config/oro/websocket_routing';
        $bundleDir = dirname(__DIR__) . '/Fixtures/Bundle/TestBundle1/';
        $loader = new NullFolderYamlCumulativeFileLoader($relativePath);
        $resources = $loader->load(TestBundle1::class, $bundleDir);

        self::assertCount(2, $resources);
        self::assertInstanceOf(CumulativeResourceInfo::class, $resources[0]);
        self::assertEquals($relativePath . '/email_config.yml', $resources[0]->path);
        self::assertInstanceOf(CumulativeResourceInfo::class, $resources[1]);
        self::assertEquals($relativePath . '/entity_config_attribute_import.yml', $resources[1]->path);
    }
}
