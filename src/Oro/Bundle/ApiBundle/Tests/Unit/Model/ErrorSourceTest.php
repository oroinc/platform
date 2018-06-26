<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\ErrorSource;

class ErrorSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateByPropertyPath()
    {
        $source = ErrorSource::createByPropertyPath('test');
        self::assertEquals('test', $source->getPropertyPath());
    }

    public function testCreateByPointer()
    {
        $source = ErrorSource::createByPointer('test');
        self::assertEquals('test', $source->getPointer());
    }

    public function testCreateByParameter()
    {
        $source = ErrorSource::createByParameter('test');
        self::assertEquals('test', $source->getParameter());
    }

    public function testPropertyPath()
    {
        $source = new ErrorSource();
        self::assertNull($source->getPropertyPath());

        $source->setPropertyPath('test');
        self::assertEquals('test', $source->getPropertyPath());
    }

    public function testPointer()
    {
        $source = new ErrorSource();
        self::assertNull($source->getPointer());

        $source->setPointer('test');
        self::assertEquals('test', $source->getPointer());
    }

    public function testParameter()
    {
        $source = new ErrorSource();
        self::assertNull($source->getParameter());

        $source->setParameter('test');
        self::assertEquals('test', $source->getParameter());
    }
}
