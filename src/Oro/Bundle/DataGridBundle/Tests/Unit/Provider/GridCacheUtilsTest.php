<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Provider\Cache\GridCacheUtils;
use PHPUnit\Framework\TestCase;

class GridCacheUtilsTest extends TestCase
{
    private GridCacheUtils $gridCacheUtils;

    #[\Override]
    protected function setUp(): void
    {
        $this->gridCacheUtils = new GridCacheUtils('/tmp');
    }

    public function testGetGridConfigCacheWithFolderName(): void
    {
        $cache = $this->gridCacheUtils->getGridConfigCache('testGrid', 'testFolder');

        $this->assertStringEndsWith('testFolder/testGrid.php', $cache->getPath());
    }

    public function testGetGridConfigCacheWithLongGridName(): void
    {
        $longGridName = str_repeat('a', 300);
        $cache = $this->gridCacheUtils->getGridConfigCache($longGridName);

        $this->assertStringEndsWith(hash('sha256', $longGridName) . '.php', $cache->getPath());
    }

    /**
     * @dataProvider gridConfigCacheProvider
     */
    public function testGetGridConfigCache(string $gridName, ?string $folderName, string $expectedFileName): void
    {
        $cache = $this->gridCacheUtils->getGridConfigCache($gridName, $folderName);

        $this->assertEquals($expectedFileName, $cache->getPath());
    }

    public function gridConfigCacheProvider(): array
    {
        $longGridName = str_repeat('a', 300);
        $longFolderName = str_repeat('b', 300);
        $nonBinarySafeString = "\x80\xA0";
        $nonPrintableString = "\x01\x02\x03";

        return [
            ['testGrid', null, '/tmp/testGrid.php'],
            ['testGrid', 'testFolder', '/tmp/testFolder/testGrid.php'],
            ['test,Grid', 'testFolder', '/tmp/testFolder/test-Grid.php'],
            [',', 'testFolder', '/tmp/testFolder/-.php'],
            [$longGridName, null, '/tmp/9835fa6bf4e20a9b9ea812506302e98982721a6cf8d2cae67af57129bf21ae90.php'],
            [
                $longGridName,
                'testFolder',
                '/tmp/testFolder/9835fa6bf4e20a9b9ea812506302e98982721a6cf8d2cae67af57129bf21ae90.php'
            ],
            [
                'testGrid',
                $longFolderName,
                '/tmp/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
                . 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
                . 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
                . 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb/'
                . '2dd91354db5f0d4f4a84f699c7e0c3635dde109fdb15d63d555d0301dbc827ec.php'
            ],
            [$nonBinarySafeString, 'testFolder', '/tmp/testFolder/--.php'],
            [$nonPrintableString, 'testFolder', '/tmp/testFolder/---.php'],
        ];
    }
}
