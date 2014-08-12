<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\Entity\Enum;
use Oro\Bundle\EntityExtendBundle\Tests\Util\ReflectionUtil;

class EnumTest extends \PHPUnit_Framework_TestCase
{
    /** @var Enum */
    protected $enum;

    protected function setUp()
    {
        $this->enum = new Enum('test');
    }

    public function testGetId()
    {
        ReflectionUtil::setId($this->enum, 123);
        $this->assertEquals(123, $this->enum->getId());
    }

    public function testGetCode()
    {
        $this->assertEquals('test', $this->enum->getCode());
    }

    public function testPublicGetterAndSetter()
    {
        $this->assertFalse($this->enum->isPublic());

        $this->assertSame($this->enum, $this->enum->setPublic(true));
        $this->assertTrue($this->enum->isPublic());
    }
}
