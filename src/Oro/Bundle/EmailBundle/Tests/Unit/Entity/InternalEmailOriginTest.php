<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use PHPUnit\Framework\TestCase;

class InternalEmailOriginTest extends TestCase
{
    public function testNameGetterAndSetter(): void
    {
        $entity = new InternalEmailOrigin();
        $entity->setName('test');
        $this->assertEquals('test', $entity->getName());
    }
}
