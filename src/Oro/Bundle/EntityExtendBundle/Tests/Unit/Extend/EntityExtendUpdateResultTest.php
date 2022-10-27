<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateResult;

class EntityExtendUpdateResultTest extends \PHPUnit\Framework\TestCase
{
    public function testSuccessResult()
    {
        $result = new EntityExtendUpdateResult(true);
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testFailureResult()
    {
        $result = new EntityExtendUpdateResult(false, 'some error');
        self::assertFalse($result->isSuccessful());
        self::assertEquals('some error', $result->getFailureMessage());
    }
}
