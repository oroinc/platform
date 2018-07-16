<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class IncludedEntityDataTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeMarkedAsNewByDefault()
    {
        $data = new IncludedEntityData('path', 0);
        self::assertFalse($data->isExisting());
    }

    public function testShouldGetPath()
    {
        $data = new IncludedEntityData('path', 0, true);
        self::assertSame('path', $data->getPath());
    }

    public function testShouldGetIndex()
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertSame(123, $data->getIndex());
    }

    public function testShouldGetIsExisting()
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertTrue($data->isExisting());
    }

    public function testShouldNormalizedDataBeNullByDefault()
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertNull($data->getNormalizedData());
    }

    public function testShouldSetNormalizedData()
    {
        $data = new IncludedEntityData('path', 123, true);
        $normalizedData = ['key' => 'value'];
        $data->setNormalizedData($normalizedData);
        self::assertEquals($normalizedData, $data->getNormalizedData());
    }

    public function testShouldMetadataBeNullByDefault()
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertNull($data->getMetadata());
    }

    public function testShouldSetMetadata()
    {
        $data = new IncludedEntityData('path', 123, true);
        $metadata = new EntityMetadata();
        $data->setMetadata($metadata);
        self::assertSame($metadata, $data->getMetadata());
    }
}
