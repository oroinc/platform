<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

class EntityInstantiatorForObjectTest extends \PHPUnit_Framework_TestCase
{
    const OBJECT_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    protected function setUp()
    {
        $doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getEntityMetadataForClass')
            ->with($this->isType('string'), false)
            ->willReturn(null);

        $this->entityInstantiator = new EntityInstantiator($doctrineHelper);
    }

    public function testInstantiateObjectWithoutConstructor()
    {
        $this->assertInstanceOf(
            self::OBJECT_NAMESPACE . 'Group',
            $this->entityInstantiator->instantiate(self::OBJECT_NAMESPACE . 'Group')
        );
    }

    public function testInstantiateObjectWithConstructorWithRequiredArguments()
    {
        $this->assertInstanceOf(
            self::OBJECT_NAMESPACE . 'Category',
            $this->entityInstantiator->instantiate(self::OBJECT_NAMESPACE . 'Category')
        );
    }

    public function testInstantiateObjectWithConstructorWithoutRequiredArgumentsAndWithToMany()
    {
        $object = $this->entityInstantiator->instantiate(self::OBJECT_NAMESPACE . 'User');
        $this->assertInstanceOf(
            self::OBJECT_NAMESPACE . 'User',
            $object
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $object->getGroups()
        );
    }

    public function testInstantiateObjectWithConstructorWithRequiredArgumentsAndWithToMany()
    {
        $object = $this->entityInstantiator->instantiate(self::OBJECT_NAMESPACE . 'Company');
        $this->assertInstanceOf(
            self::OBJECT_NAMESPACE . 'Company',
            $object
        );
        $this->assertNull(
            $object->getGroups()
        );
    }
}
