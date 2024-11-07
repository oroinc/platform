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

    /**
     * @dataProvider isAbsoluteUrlDataProvider
     */
    public function testIsAbsoluteUrl(string $url, bool $expected): void
    {
        self::assertEquals($expected, UrlUtil::isAbsoluteUrl($url));
    }

    public function isAbsoluteUrlDataProvider(): \Generator
    {
        yield 'empty url' => ['url' => '', 'expected' => false];
        yield 'absolute path' => ['url' => '/absolute/page/path', 'expected' => false];
        yield 'relative path' => ['url' => 'relative/page/path', 'expected' => false];
        yield 'absolute https url' => ['url' => 'https://example.com', 'expected' => true];
        yield 'absolute http url' => ['url' => 'http://example.com', 'expected' => true];
        yield 'absolute protocol-less url' => ['url' => '//example.com', 'expected' => true];
    }

    /**
     * @dataProvider getHttpHostDataProvider
     */
    public function testGetHttpHost(string $url, string $expected): void
    {
        self::assertEquals($expected, UrlUtil::getHttpHost($url));
    }

    public function getHttpHostDataProvider(): \Generator
    {
        yield 'empty url' => ['url' => '', 'expected' => ''];
        yield 'absolute path' => ['url' => '/absolute/page/path', 'expected' => ''];
        yield 'relative path' => ['url' => 'relative/page/path', 'expected' => ''];
        yield 'absolute https url' => ['url' => 'https://example.com', 'expected' => 'example.com'];
        yield 'absolute https url with port' => ['url' => 'https://example.com:4343', 'expected' => 'example.com:4343'];
        yield 'absolute http url' => ['url' => 'http://example.com', 'expected' => 'example.com'];
        yield 'absolute http url with port' => ['url' => 'http://example.com:8080', 'expected' => 'example.com:8080'];
        yield 'absolute protocol-less url' => ['url' => '//example.com', 'expected' => 'example.com'];
        yield 'absolute protocol-less url with port' => [
            'url' => '//example.com:8080',
            'expected' => 'example.com:8080',
        ];
    }

    /**
     * @dataProvider getSchemeAndHttpHostDataProvider
     */
    public function testGetSchemeAndHttpHost(string $url, string $expected): void
    {
        self::assertEquals($expected, UrlUtil::getSchemeAndHttpHost($url));
    }

    public function getSchemeAndHttpHostDataProvider(): \Generator
    {
        yield 'empty url' => ['url' => '', 'expected' => ''];
        yield 'absolute path' => ['url' => '/absolute/page/path', 'expected' => ''];
        yield 'relative path' => ['url' => 'relative/page/path', 'expected' => ''];
        yield 'absolute https url' => ['url' => 'https://example.com/page/path', 'expected' => 'https://example.com'];
        yield 'absolute https url with port' => [
            'url' => 'https://example.com:4343/page/path',
            'expected' => 'https://example.com:4343'
        ];
        yield 'absolute http url' => ['url' => 'http://example.com/page/path', 'expected' => 'http://example.com'];
        yield 'absolute http url with port' => [
            'url' => 'http://example.com:8080/page/path',
            'expected' => 'http://example.com:8080'
        ];
        yield 'absolute protocol-less url' => ['url' => '//example.com/page/path', 'expected' => '//example.com'];
        yield 'absolute protocol-less url with port' => [
            'url' => '//example.com:8080/page/path',
            'expected' => '//example.com:8080',
        ];
    }

    /**
     * @dataProvider addQueryParametersDataProvider
     */
    public function testAddQueryParameters(string $url, array $extraParameters, bool $override, string $expected): void
    {
        self::assertEquals($expected, UrlUtil::addQueryParameters($url, $extraParameters, $override));
    }

    public function addQueryParametersDataProvider(): \Generator
    {
        yield 'empty' => ['url' => '', 'extraParameters' => [], 'override' => false, 'expected' => ''];
        yield 'absolute url' => [
            'url' => 'http://example.com',
            'extraParameters' => [],
            'override' => false,
            'expected' => 'http://example.com',
        ];
        yield 'absolute url with extra parameters' => [
            'url' => 'http://example.com',
            'extraParameters' => ['sample_key' => 'sample_value'],
            'override' => false,
            'expected' => 'http://example.com?sample_key=sample_value',
        ];
        yield 'absolute url with existing query parameters and extra parameters' => [
            'url' => 'http://example.com?sample_key1=sample_value1',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => false,
            'expected' => 'http://example.com?sample_key1=sample_value1&sample_key2=sample_value2',
        ];
        yield 'absolute url with overlapping existing query parameters and extra parameters' => [
            'url' => 'http://example.com?sample_key1=sample_value1',
            'extraParameters' => ['sample_key1' => 'sample_value_new', 'sample_key2' => 'sample_value2'],
            'override' => false,
            'expected' => 'http://example.com?sample_key1=sample_value1&sample_key2=sample_value2',
        ];
        yield 'absolute url with overriding existing query parameters and extra parameters' => [
            'url' => 'http://example.com?sample_key1=sample_value1',
            'extraParameters' => ['sample_key1' => 'sample_value_new', 'sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => 'http://example.com?sample_key1=sample_value_new&sample_key2=sample_value2',
        ];
        yield 'absolute url with fragment' => [
            'url' => 'http://example.com#sample-fragment',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => 'http://example.com?sample_key2=sample_value2#sample-fragment',
        ];
        yield 'absolute url with fragment and existing query parameters' => [
            'url' => 'http://example.com?sample_key1=sample_value1#sample-fragment',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => 'http://example.com?sample_key1=sample_value1&sample_key2=sample_value2#sample-fragment',
        ];
        yield 'relative url' => [
            'url' => '/sample/page',
            'extraParameters' => [],
            'override' => false,
            'expected' => '/sample/page',
        ];
        yield 'relative url with extra parameters' => [
            'url' => '/sample/page',
            'extraParameters' => ['sample_key' => 'sample_value'],
            'override' => false,
            'expected' => '/sample/page?sample_key=sample_value',
        ];
        yield 'relative url with existing query parameters and extra parameters' => [
            'url' => '/sample/page?sample_key1=sample_value1',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => false,
            'expected' => '/sample/page?sample_key1=sample_value1&sample_key2=sample_value2',
        ];
        yield 'relative url with overlapping existing query parameters and extra parameters' => [
            'url' => '/sample/page?sample_key1=sample_value1',
            'extraParameters' => ['sample_key1' => 'sample_value_new', 'sample_key2' => 'sample_value2'],
            'override' => false,
            'expected' => '/sample/page?sample_key1=sample_value1&sample_key2=sample_value2',
        ];
        yield 'relative url with overriding existing query parameters and extra parameters' => [
            'url' => '/sample/page?sample_key1=sample_value1',
            'extraParameters' => ['sample_key1' => 'sample_value_new', 'sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => '/sample/page?sample_key1=sample_value_new&sample_key2=sample_value2',
        ];
        yield 'relative url with fragment' => [
            'url' => '/sample/page#sample-fragment',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => '/sample/page?sample_key2=sample_value2#sample-fragment',
        ];
        yield 'relative url with fragment and existing query parameters' => [
            'url' => '/sample/page?sample_key1=sample_value1#sample-fragment',
            'extraParameters' => ['sample_key2' => 'sample_value2'],
            'override' => true,
            'expected' => '/sample/page?sample_key1=sample_value1&sample_key2=sample_value2#sample-fragment',
        ];
    }
}
