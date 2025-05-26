<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use PHPUnit\Framework\TestCase;

class NotResolvedIdentifierTest extends TestCase
{
    public function testObject(): void
    {
        $value = 'test';
        $class = 'Test\Class';
        $identifier = new NotResolvedIdentifier($value, $class);
        self::assertSame($value, $identifier->getValue());
        self::assertSame($class, $identifier->getEntityClass());
    }
}
