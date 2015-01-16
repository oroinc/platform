<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\SyncCacheInterface;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $cacheClass
     * @param string $id
     * @param string $namespace
     * @param string $expectedFileName
     *
     * @dataProvider getFilenameProvider
     */
    public function testGetFilename($cacheClass, $id, $namespace, $expectedFileName)
    {
        $cache = $this->getMockBuilder($cacheClass)
            ->setConstructorArgs(['dir', '.ext'])
            ->setMethods(['fetch', 'getNamespace'])
            ->getMock();

        $cache->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));

        $result = self::callProtectedMethod($cache, 'getFilename', [$id]);
        $this->assertEquals(
            $expectedFileName,
            str_replace(realpath('dir'), 'dir', $result)
        );
    }

    /**
     * @param string $cacheClass
     *
     * @dataProvider syncProvider
     */
    public function testSync($cacheClass)
    {
        $namespace = '123';

        /** @var \PHPUnit_Framework_MockObject_MockObject|SyncCacheInterface $cache */
        $cache = $this->getMockBuilder($cacheClass)
            ->disableOriginalConstructor()
            ->setMethods(['setNamespace', 'getNamespace'])
            ->getMock();

        $cache->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $cache->expects($this->once())
            ->method('setNamespace')
            ->with($namespace);

        $cache->sync();
    }

    /**
     * @return array
     */
    public static function getFilenameProvider()
    {
        return [
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test',
                null,
                'dir' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test',
                'namespace',
                'dir' . DIRECTORY_SEPARATOR . 'namespace' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'dir' . DIRECTORY_SEPARATOR . 'namespace' . DIRECTORY_SEPARATOR . 'testfile.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test',
                null,
                'dir' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test',
                'namespace',
                'dir' . DIRECTORY_SEPARATOR . 'namespace' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'dir' . DIRECTORY_SEPARATOR . 'namespace' . DIRECTORY_SEPARATOR . 'testfile.ext',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function syncProvider()
    {
        return [
            ['Oro\Bundle\CacheBundle\Provider\FilesystemCache'],
            ['Oro\Bundle\CacheBundle\Provider\PhpFileCache'],
        ];
    }

    /**
     * @param  mixed  $obj
     * @param  string $methodName
     * @param  array  $args
     * @return mixed
     */
    public static function callProtectedMethod($obj, $methodName, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
