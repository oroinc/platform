<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Value;
use PHPUnit\Framework\TestCase;

class ValueTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $value = 'testValue';
        $encoding = 'testEncoding';
        $obj = new Value($value, $encoding);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($encoding, $obj->getEncoding());
    }
}
