<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Generator;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheDataConverterInterface;
use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectCacheKeyGeneratorTest extends TestCase
{
    private ObjectCacheDataConverterInterface&MockObject $converter;
    private ObjectCacheKeyGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = $this->createMock(ObjectCacheDataConverterInterface::class);
        $this->generator = new ObjectCacheKeyGenerator($this->converter);
    }

    public function testGenerate(): void
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
