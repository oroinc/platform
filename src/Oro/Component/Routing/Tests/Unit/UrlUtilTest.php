<?php

namespace Oro\Component\Routing\Tests\Unit;

use Oro\Component\Routing\UrlUtil;

class UrlUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getAbsolutePathDataProvider
     */
    public function testGetAbsolutePath(string $path, string $baseUrl, string $result)
    {
        self::assertEquals($result, UrlUtil::getAbsolutePath($path, $baseUrl));
    }

    /**
     * @dataProvider getPathInfoDataProvider
     */
    public function testGetPathInfo(string $path, string $baseUrl, string $result)
    {
        self::assertEquals($result, UrlUtil::getPathInfo($path, $baseUrl));
    }

    /**
     * @dataProvider joinDataProvider
     */
    public function testJoin(string $path1, string $path2, string $result)
    {
        self::assertEquals($result, UrlUtil::join($path1, $path2));
    }

    public function getAbsolutePathDataProvider(): array
    {
        return [
            ['', '', ''],
            ['', '/', '/'],
            ['/', '', '/'],
            ['/', '/', '/'],
            ['', 'base', '/base'],
            ['', 'base/', '/base/'],
            ['', '/base', '/base'],
            ['', '/base/', '/base/'],
            ['base', 'base', '/base'],
            ['base/', 'base/', '/base/'],
            ['/base', '/base', '/base'],
            ['/base/', '/base/', '/base/'],
            ['/path', '/', '/path'],
            ['/path', '/base', '/base/path'],
            ['/path', '/base/', '/base/path'],
            ['/path', 'base', '/base/path'],
            ['/path/file', '/', '/path/file'],
            ['/path/file', '/base', '/base/path/file'],
            ['/path/file', '/base/', '/base/path/file'],
            ['/path/file', 'base', '/base/path/file'],
            ['path', '', '/path'],
            ['path', '/', '/path'],
            ['path', '/base', '/base/path'],
            ['path', '/base/', '/base/path'],
            ['path', 'base', '/base/path'],
            ['path/file', '', '/path/file'],
            ['path/file', '/', '/path/file'],
            ['path/file', '/base', '/base/path/file'],
            ['path/file', '/base/', '/base/path/file'],
            ['path/file', 'base', '/base/path/file'],
            ['/base/path', '/base', '/base/path'],
            ['/base/path', '/base/', '/base/path'],
            ['base/path', 'base', '/base/path'],
            ['base/path', 'base/', '/base/path'],
            ['/base/path', 'base', '/base/base/path'],
            ['/base/path', 'base/', '/base/base/path'],
            ['/путь', '/база', '/база/путь'],
            ['/путь', '/база/', '/база/путь'],
        ];
    }

    public function getPathInfoDataProvider(): array
    {
        return [
            ['', '', ''],
            ['', '/', '/'],
            ['/', '', '/'],
            ['/', '/', '/'],
            ['', 'base', '/'],
            ['', 'base/', '/'],
            ['', '/base', '/'],
            ['', '/base/', '/'],
            ['base', 'base', '/'],
            ['base/', 'base/', '/'],
            ['/base', '/base', '/'],
            ['/base/', '/base/', '/'],
            ['/path', '', '/path'],
            ['path', '', '/path'],
            ['/path', '/', '/path'],
            ['path', '/', '/path'],
            ['/base/path', '/base', '/path'],
            ['/base/path', '/base/', '/path'],
            ['/base/path', 'base', '/base/path'],
            ['base/path', 'base', '/path'],
            ['/base/path', 'base/', '/base/path'],
            ['base/path', 'base/', '/path'],
            ['/dir/base/path', '/base', '/dir/base/path'],
            ['/dir/base/path', '/base/', '/dir/base/path'],
            ['/dir/base/path', 'base', '/dir/base/path'],
            ['/dir/base/path', 'base/', '/dir/base/path'],
            ['dir/base/path', '/base', '/dir/base/path'],
            ['dir/base/path', '/base/', '/dir/base/path'],
            ['dir/base/path', 'base', '/dir/base/path'],
            ['dir/base/path', 'base/', '/dir/base/path'],
            ['/dir/base/path', '/dir/base', '/path'],
            ['/dir/base/path', '/dir/base/', '/path'],
            ['/dir/base/path', 'dir/base', '/dir/base/path'],
            ['/dir/base/path', 'dir/base/', '/dir/base/path'],
            ['dir/base/path', '/dir/base', '/dir/base/path'],
            ['dir/base/path', '/dir/base/', '/dir/base/path'],
            ['dir/base/path', 'dir/base', '/path'],
            ['dir/base/path', 'dir/base/', '/path'],
            ['/база/путь', '/база', '/путь'],
            ['/база/путь', '/база/', '/путь'],
        ];
    }

    public function joinDataProvider(): array
    {
        return [
            ['', '', ''],
            ['/', '', '/'],
            ['', '/', '/'],
            ['/', '/', '/'],
            ['path1', '', 'path1'],
            ['path1/', '', 'path1/'],
            ['/path1', '', '/path1'],
            ['/path1/', '', '/path1/'],
            ['path1', '/', 'path1'],
            ['path1/', '/', 'path1/'],
            ['/path1', '/', '/path1'],
            ['/path1/', '/', '/path1/'],
            ['', 'path2', 'path2'],
            ['', 'path2/', 'path2/'],
            ['', '/path2', '/path2'],
            ['', '/path2/', '/path2/'],
            ['/', 'path2', '/path2'],
            ['/', 'path2/', '/path2/'],
            ['/', '/path2', '/path2'],
            ['/', '/path2/', '/path2/'],
            ['path1', 'path2', 'path1/path2'],
            ['path1', 'path2/', 'path1/path2/'],
            ['path1', '/path2', 'path1/path2'],
            ['path1', '/path2/', 'path1/path2/'],
            ['path1/', 'path2', 'path1/path2'],
            ['path1/', 'path2/', 'path1/path2/'],
            ['path1/', '/path2', 'path1/path2'],
            ['path1/', '/path2/', 'path1/path2/'],
            ['/path1', 'path2', '/path1/path2'],
            ['/path1', 'path2/', '/path1/path2/'],
            ['/path1', '/path2', '/path1/path2'],
            ['/path1', '/path2/', '/path1/path2/'],
            ['/path1/', 'path2', '/path1/path2'],
            ['/path1/', 'path2/', '/path1/path2/'],
            ['/path1/', '/path2', '/path1/path2'],
            ['/path1/', '/path2/', '/path1/path2/'],
        ];
    }
}
