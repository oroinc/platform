<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Manager\DTO\ItemId;
use PHPUnit\Framework\TestCase;

class ItemIdTest extends TestCase
{
    public function testConstructor(): void
    {
        $obj = new ItemId(10, 20);
        $this->assertEquals(10, $obj->getUid());
        $this->assertEquals(20, $obj->getUidValidity());
    }

    public function testGettersAndSetters(): void
    {
        $obj = new ItemId(1, 2);
        $obj
            ->setUid(10)
            ->setUidValidity(20);
        $this->assertEquals(10, $obj->getUid());
        $this->assertEquals(20, $obj->getUidValidity());
    }
}
