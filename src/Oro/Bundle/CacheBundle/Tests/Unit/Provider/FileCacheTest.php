<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFilenameProvider
     */
    public function testGetFilename($cacheClass, $id, $expectedFileName)
    {
        $cache = $this->getMockBuilder($cacheClass)
            ->disableOriginalConstructor()
            ->setMethods(['fetch'])
            ->getMock();

        self::setProtectedProperty($cache, 'directory', 'dir');
        self::setProtectedProperty($cache, 'extension', '.ext');

        $this->assertEquals(
            $expectedFileName,
            self::callProtectedMethod($cache, 'getFilename', array($id))
        );
    }

    /**
     * @dataProvider syncProvider
     */
    public function testSync($cacheClass)
    {
        $namespace = '123';

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

    public static function getFilenameProvider()
    {
        return [
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test',
                'dir' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                'test\\\\//::""**??<<>>||file',
                'dir' . DIRECTORY_SEPARATOR . 'testfile.ext'
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test',
                'dir' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                'Oro\Bundle\CacheBundle\Provider\PhpFileCache',
                'test\\\\//::""**??<<>>||file',
                'dir' . DIRECTORY_SEPARATOR . 'testfile.ext'
            ],
        ];
    }

    public static function syncProvider()
    {
        return [
            ['Oro\Bundle\CacheBundle\Provider\FilesystemCache'],
            ['Oro\Bundle\CacheBundle\Provider\PhpFileCache'],
        ];
    }

    /**
     * @param mixed  $obj
     * @param string $propName
     * @param mixed  $val
     */
    public static function setProtectedProperty($obj, $propName, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop = $class->getProperty($propName);
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
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
