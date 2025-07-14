<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateResult;
use PHPUnit\Framework\TestCase;

class EntityExtendUpdateResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $result = new EntityExtendUpdateResult(true);
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testFailureResult(): void
    {
        $result = new EntityExtendUpdateResult(false, 'some error');
        self::assertFalse($result->isSuccessful());
        self::assertEquals('some error', $result->getFailureMessage());
    }
}
