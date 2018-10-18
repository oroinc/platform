<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Value;

class ValueTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorAndGetters()
    {
        $value = 'testValue';
        $encoding = 'testEncoding';
        $obj = new Value($value, $encoding);

        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($encoding, $obj->getEncoding());
    }
}
