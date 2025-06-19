<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use PHPUnit\Framework\TestCase;

class ErrorMetaPropertyTest extends TestCase
{
    public function testCreateWithoutDataType(): void
    {
        $metaProperty = new ErrorMetaProperty('value');
        self::assertEquals('value', $metaProperty->getValue());
        self::assertEquals('string', $metaProperty->getDataType());
    }

    public function testCreateWithDataType(): void
    {
        $metaProperty = new ErrorMetaProperty('1,2,3', 'integer[]');
        self::assertEquals('1,2,3', $metaProperty->getValue());
        self::assertEquals('integer[]', $metaProperty->getDataType());
    }

    public function testChangeValue(): void
    {
        $metaProperty = new ErrorMetaProperty('val1');
        self::assertEquals('val1', $metaProperty->getValue());

        $metaProperty->setValue('val2');
        self::assertEquals('val2', $metaProperty->getValue());
    }
}
