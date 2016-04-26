<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\ErrorSource;

class ErrorSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateByPropertyPath()
    {
        $source = ErrorSource::createByPropertyPath('test');
        $this->assertEquals('test', $source->getPropertyPath());
    }

    public function testCreateByPointer()
    {
        $source = ErrorSource::createByPointer('test');
        $this->assertEquals('test', $source->getPointer());
    }

    public function testCreateByParameter()
    {
        $source = ErrorSource::createByParameter('test');
        $this->assertEquals('test', $source->getParameter());
    }

    public function testPropertyPath()
    {
        $source = new ErrorSource();
        $this->assertNull($source->getPropertyPath());

        $source->setPropertyPath('test');
        $this->assertEquals('test', $source->getPropertyPath());
    }

    public function testPointer()
    {
        $source = new ErrorSource();
        $this->assertNull($source->getPointer());

        $source->setPointer('test');
        $this->assertEquals('test', $source->getPointer());
    }

    public function testParameter()
    {
        $source = new ErrorSource();
        $this->assertNull($source->getParameter());

        $source->setParameter('test');
        $this->assertEquals('test', $source->getParameter());
    }
}
