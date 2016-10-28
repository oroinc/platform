<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class IncludedObjectDataTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeMarkedAsNewByDefault()
    {
        $data = new IncludedObjectData('path', 0);
        self::assertFalse($data->isExisting());
    }

    public function testShouldGetPath()
    {
        $data = new IncludedObjectData('path', 0, true);
        self::assertSame('path', $data->getPath());
    }

    public function testShouldGetIndex()
    {
        $data = new IncludedObjectData('path', 123, true);
        self::assertSame(123, $data->getIndex());
    }

    public function testShouldGetIsExisting()
    {
        $data = new IncludedObjectData('path', 123, true);
        self::assertTrue($data->isExisting());
    }

    public function testShouldNormalizedDataBeNullByDefault()
    {
        $data = new IncludedObjectData('path', 123, true);
        self::assertNull($data->getNormalizedData());
    }

    public function testShouldSetNormalizedData()
    {
        $data = new IncludedObjectData('path', 123, true);
        $normalizedData = ['key' => 'value'];
        $data->setNormalizedData($normalizedData);
        self::assertEquals($normalizedData, $data->getNormalizedData());
    }

    public function testShouldMetadataBeNullByDefault()
    {
        $data = new IncludedObjectData('path', 123, true);
        self::assertNull($data->getMetadata());
    }

    public function testShouldSetMetadata()
    {
        $data = new IncludedObjectData('path', 123, true);
        $metadata = new EntityMetadata();
        $data->setMetadata($metadata);
        self::assertSame($metadata, $data->getMetadata());
    }
}
