<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

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

    public function testNameGetterAndSetter()
    {
        $this->assertSame($this->enum, $this->enum->setName('test'));
        $this->assertEquals('test', $this->enum->getName());
    }

    public function testLocaleGetterAndSetter()
    {
        $this->assertSame($this->enum, $this->enum->setLocale('test'));
        $this->assertEquals('test', $this->enum->getLocale());
    }

    public function testTranslationsGetterAndSetter()
    {
        $this->assertCount(0, $this->enum->getTranslations());

        $translation = $this->getMock('Oro\Bundle\EntityExtendBundle\Entity\EnumTranslation');
        $translation->expects($this->once())
            ->method('setObject')
            ->with($this->identicalTo($this->enum));

        $translations = new ArrayCollection([$translation]);
        $this->assertSame($this->enum, $this->enum->setTranslations($translations));

        $this->assertSame($translations, $this->enum->getTranslations());
    }
}
