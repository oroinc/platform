<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;

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
}
