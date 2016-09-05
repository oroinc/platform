<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Symfony\Component\Filesystem\Filesystem;

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
        $fs = new Filesystem();
        $directory = 'dir' . uniqid();

        $cache = $this->getMockBuilder($cacheClass)
            ->setConstructorArgs([$directory, '.ext'])
            ->setMethods(['fetch', 'getNamespace'])
            ->getMock();

        $cache->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));

        $result = self::callProtectedMethod($cache, 'getFilename', [$id]);
        $this->assertEquals(
            $directory . DIRECTORY_SEPARATOR . $expectedFileName,
            str_replace(realpath($directory), $directory, $result)
        );

        $this->assertTrue($fs->exists($directory));
        $fs->remove($directory);
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
                '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test',
                'namespace',
                'namespace' . DIRECTORY_SEPARATOR . '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'namespace' . DIRECTORY_SEPARATOR . 'd3' . DIRECTORY_SEPARATOR . 'testfile.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test',
                null,
                '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test',
                'namespace',
                'namespace' . DIRECTORY_SEPARATOR . '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'namespace' . DIRECTORY_SEPARATOR . 'd3' . DIRECTORY_SEPARATOR . 'testfile.ext',
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
