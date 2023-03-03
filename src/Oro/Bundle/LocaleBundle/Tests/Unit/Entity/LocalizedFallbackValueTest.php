<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LocalizedFallbackValueTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
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

    public function testToString(): void
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

    public function testClone(): void
    {
        $id = 123;
        $value = new LocalizedFallbackValue();
        ReflectionUtil::setId($value, $id);

        $clonedValue = clone $value;
        $this->assertEquals($id, $value->getId());
        $this->assertNull($clonedValue->getId());
    }

    public function testGetFallback(): void
    {
        $this->assertIsArray(LocalizedFallbackValue::getFallbacks());
        $this->assertNotEmpty(LocalizedFallbackValue::getFallbacks());
    }
}
