<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\ErrorSource;
use PHPUnit\Framework\TestCase;

class ErrorSourceTest extends TestCase
{
    public function testCreateByPropertyPath(): void
    {
        $source = ErrorSource::createByPropertyPath('test');
        self::assertEquals('test', $source->getPropertyPath());
    }

    public function testCreateByPointer(): void
    {
        $source = ErrorSource::createByPointer('test');
        self::assertEquals('test', $source->getPointer());
    }

    public function testCreateByParameter(): void
    {
        $source = ErrorSource::createByParameter('test');
        self::assertEquals('test', $source->getParameter());
    }

    public function testPropertyPath(): void
    {
        $source = new ErrorSource();
        self::assertNull($source->getPropertyPath());

        $source->setPropertyPath('test');
        self::assertEquals('test', $source->getPropertyPath());
    }

    public function testPointer(): void
    {
        $source = new ErrorSource();
        self::assertNull($source->getPointer());

        $source->setPointer('test');
        self::assertEquals('test', $source->getPointer());
    }

    public function testParameter(): void
    {
        $source = new ErrorSource();
        self::assertNull($source->getParameter());

        $source->setParameter('test');
        self::assertEquals('test', $source->getParameter());
    }
}
