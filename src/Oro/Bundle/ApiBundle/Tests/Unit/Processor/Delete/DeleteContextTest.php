<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;


class DeleteContextTest extends DeleteContextTestCase
{
    public function testContext()
    {
        $this->assertFalse($this->context->hasObject());
        $object = new \stdClass();
        $this->context->setObject($object);
        $this->assertSame($object, $this->context->getObject());
        $this->context->removeObject();
        $this->assertFalse($this->context->hasObject());
    }
}
