<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;

class NotResolvedIdentifierTest extends \PHPUnit\Framework\TestCase
{
    public function testObject()
    {
        $value = 'test';
        $class = 'Test\Class';
        $identifier = new NotResolvedIdentifier($value, $class);
        self::assertSame($value, $identifier->getValue());
        self::assertSame($class, $identifier->getEntityClass());
    }
}
