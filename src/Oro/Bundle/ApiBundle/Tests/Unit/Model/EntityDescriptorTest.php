<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EntityDescriptor;

class EntityDescriptorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $entityDescriptor = new EntityDescriptor('id', 'class', 'title');
        $this->assertEquals('id', $entityDescriptor->getId());
        $this->assertEquals('class', $entityDescriptor->getClass());
        $this->assertEquals('title', $entityDescriptor->getTitle());
    }

    public function testId()
    {
        $entityDescriptor = new EntityDescriptor();
        $this->assertNull($entityDescriptor->getId());

        $entityDescriptor->setId('test');
        $this->assertEquals('test', $entityDescriptor->getId());
    }

    public function testClass()
    {
        $entityDescriptor = new EntityDescriptor();
        $this->assertNull($entityDescriptor->getClass());

        $entityDescriptor->setClass('test');
        $this->assertEquals('test', $entityDescriptor->getClass());
    }

    public function testTitle()
    {
        $entityDescriptor = new EntityDescriptor();
        $this->assertNull($entityDescriptor->getTitle());

        $entityDescriptor->setTitle('test');
        $this->assertEquals('test', $entityDescriptor->getTitle());
    }
}
