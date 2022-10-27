<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Generator;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheDataConverterInterface;
use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;

class ObjectCacheKeyGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectCacheDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converter;

    /**
     * @var ObjectCacheKeyGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(ObjectCacheDataConverterInterface::class);
        $this->generator = new ObjectCacheKeyGenerator($this->converter);
    }

    public function testGenerate()
    {
        $object = new \stdClass('first');
        $expectedString = 'someExpectedString';
        $this->converter->expects($this->once())
            ->method('convertToString')
            ->with($object, 'someScope')
            ->willReturn($expectedString);
        $cacheKey = $this->generator->generate($object, 'someScope');
        self::assertEquals(sha1(get_class($object) . $expectedString), $cacheKey);
    }
}
