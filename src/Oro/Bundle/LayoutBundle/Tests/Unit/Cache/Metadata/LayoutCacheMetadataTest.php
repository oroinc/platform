<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use PHPUnit\Framework\TestCase;
use stdClass;

class LayoutCacheMetadataTest extends TestCase
{
    public function testAccessors()
    {
        $metadata = new LayoutCacheMetadata();

        $this->assertNull($metadata->getMaxAge());
        $this->assertEquals([], $metadata->getVaryBy());
        $this->assertEquals([], $metadata->getTags());

        $metadata
            ->setMaxAge(900)
            ->setVaryBy(['foo' => 5, 'bar' => 'some string'])
            ->setTags(['foo_1', 'bar_2']);

        $this->assertEquals(900, $metadata->getMaxAge());
        $this->assertEquals(['foo' => 5, 'bar' => 'some string'], $metadata->getVaryBy());
        $this->assertEquals(['foo_1', 'bar_2'], $metadata->getTags());
    }

    public function testExceptionOnSetTags()
    {
        $this->expectExceptionMessage(
            'The value of the "cache.tags.0" block option is expected to be a scalar but got "stdClass"'
        );
        $this->expectException(InvalidLayoutCacheMetadataException::class);

        (new LayoutCacheMetadata())
            ->setTags([new stdClass(), 'bar_2']);
    }

    public function testExceptionOnSetVaryBy()
    {
        $this->expectExceptionMessage(
            'The value of the "cache.varyBy.bar" block option is expected to be a scalar but got "stdClass".'
        );
        $this->expectException(InvalidLayoutCacheMetadataException::class);

        (new LayoutCacheMetadata())
            ->setVaryBy(['foo' => 'bar_2', 'bar' => new stdClass()]);
    }
}
