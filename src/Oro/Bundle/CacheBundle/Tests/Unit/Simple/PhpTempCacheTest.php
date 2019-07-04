<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Simple;

use Oro\Bundle\CacheBundle\Simple\PhpTempCache;

class PhpTempCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var PhpTempCache */
    private $cache;

    /**
     * @return array
     */
    private static function getDataTypes(): array
    {
        return [
            'null' => null,
            'true' => true,
            'false' => false,
            'zero' => 0,
            'int' => 42,
            'float' => 49.5,
            'string' => 'string',
            'empty array' => [],
            'filled array' => [1, 2, 3],
            'empty object' => new \StdClass(),
            'filled object' => (object)['attr' => 'value'],
        ];
    }

    protected function setUp(): void
    {
        $this->cache = new PhpTempCache();
    }

    /**
     * @dataProvider setGetDataProvider
     * @param mixed $key
     * @param mixed $value
     */
    public function testSetGetSingle($key, $value): void
    {
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
        $this->assertFalse($this->cache->get($key, false));

        $this->cache->set($key, $value);

        $this->assertTrue($this->cache->has($key));
        $this->assertCacheValue($value, $this->cache->get($key));
        $this->assertIndexConsistency();
    }

    /**
     * @return \Generator
     */
    public function setGetDataProvider(): \Generator
    {
        foreach (self::getDataTypes() as $keyType => $key) {
            if (is_scalar($key)) {
                foreach (self::getDataTypes() as $valueType => $value) {
                    yield "{$keyType} key with {$valueType} value" => ['key' => $key, 'value' => $value];
                }
            }
        }
    }

    public function testSetGetMultiple(): void
    {
        $dataTypes = self::getDataTypes();
        $keys = array_keys($dataTypes);

        $exceptedEmpty = array_fill_keys($keys, null);
        $this->assertSame($exceptedEmpty, $this->cache->getMultiple($keys));
        $this->assertTrue($this->cache->setMultiple($dataTypes));
        $this->assertEquals($dataTypes, $this->cache->getMultiple($keys));
        $this->assertTrue($this->cache->deleteMultiple($keys));
        $this->assertSame($exceptedEmpty, $this->cache->getMultiple($keys));
    }

    public function testIndexManipulations(): void
    {
        $dataTypes = self::getDataTypes();
        $this->cache->setMultiple($dataTypes);

        $this->assertIndexConsistency();

        foreach ($dataTypes as $key => $value) {
            $this->assertCacheValue($value, $this->cache->get($key));
            $this->cache->delete($key);
            $this->assertSame('default', $this->cache->get($key, 'default'));
            $this->assertIndexConsistency();

            $this->cache->set($key, $value);
            $this->assertCacheValue($value, $this->cache->get($key));
            $this->assertIndexConsistency();
        }
    }

    /**
     * @param mixed $excepted
     * @param mixed $actual
     */
    private function assertCacheValue($excepted, $actual): void
    {
        if (is_object($excepted)) {
            $this->assertEquals($excepted, $actual);
        } else {
            $this->assertSame($excepted, $actual);
        }
    }

    private function assertIndexConsistency(): void
    {
        $reflectionClass = new \ReflectionClass($this->cache);

        $property = $reflectionClass->getProperty('index');
        $property->setAccessible(true);
        $index = $property->getValue($this->cache);

        $property = $reflectionClass->getProperty('deleted');
        $property->setAccessible(true);
        $deleted = $property->getValue($this->cache);

        $property = $reflectionClass->getProperty('dataStorage');
        $property->setAccessible(true);

        $sortedFullIndex = array_merge(
            array_values($index),
            array_values($deleted)
        );

        usort($sortedFullIndex, function ($a, $b) {
            return $a[0] - $b[0];
        });

        $pointer = 0;
        foreach ($sortedFullIndex as $index) {
            $this->assertSame($pointer, $index[0]);
            $this->assertGreaterThan(0, $index[1]);
            $pointer += $index[1];
        }
    }
}
