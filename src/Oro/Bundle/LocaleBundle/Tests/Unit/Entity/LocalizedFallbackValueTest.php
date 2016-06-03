<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class LocalizedFallbackValueTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['localization', new Localization()],
            ['localization', null],
            ['fallback', FallbackType::SYSTEM],
            ['string', 'string'],
            ['text', 'text'],
        ];

        $this->assertPropertyAccessors(new LocalizedFallbackValue(), $properties);
    }

    public function testToString()
    {
        $stringValue = new LocalizedFallbackValue();
        $stringValue->setString('string');
        $this->assertEquals('string', (string)$stringValue);

        $textValue = new LocalizedFallbackValue();
        $textValue->setText('text');
        $this->assertEquals('text', (string)$textValue);

        $emptyValue = new LocalizedFallbackValue();
        $this->assertEquals('', (string)$emptyValue);
    }

    public function testClone()
    {
        $id = 123;
        $value = new LocalizedFallbackValue();

        $reflection = new \ReflectionProperty(get_class($value), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($value, $id);

        $clonedValue = clone $value;
        $this->assertEquals($id, $value->getId());
        $this->assertNull($clonedValue->getId());
    }

    public function testGetFallback()
    {
        $this->assertInternalType('array', LocalizedFallbackValue::getFallbacks());
        $this->assertNotEmpty('array', LocalizedFallbackValue::getFallbacks());
    }
}
