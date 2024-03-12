<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Attribute\Reader;

use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\Class\ClassWithAttributes;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\Class\SimpleClass;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\ClassTestAttribute;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\MethodTestAttribute;
use Oro\Component\PhpUtils\Tests\Unit\Fixtures\PhpUtilsAttribute\PropertyTestAttribute;

class AttributeReaderTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->reader = new AttributeReader();
    }

    public function testGetClassAttributeWithDefinedAttributes()
    {
        $reflection = new \ReflectionClass(ClassWithAttributes::class);

        $attributeName = ClassTestAttribute::class;
        $attributeValues = $this->reader->getClassAttribute($reflection, $attributeName);

        $this->assertEquals(777, $attributeValues->id);
        $this->assertEquals('CustomClass', $attributeValues->name);
        $this->assertEquals(['custom' => 'CustomClassMode'], $attributeValues->mode);
    }

    public function testGetClassAttributeWithoutDefinedAttributes()
    {
        $reflection = new \ReflectionClass(SimpleClass::class);

        $attributeValues = $this->reader->getClassAttribute($reflection, ClassTestAttribute::class);

        $this->assertNull($attributeValues);
    }

    public function testGetPropertyAttributeWithDefinedAttributes()
    {
        $reflection = new \ReflectionClass(ClassWithAttributes::class);
        $nameProperty = $reflection->getProperty('foo');

        $attributeValues = $this->reader->getPropertyAttribute($nameProperty, PropertyTestAttribute::class);

        $this->assertEquals(555, $attributeValues->id);
        $this->assertEquals('CustomProperty', $attributeValues->name);
        $this->assertEquals(['custom' => 'CustomPropertyMode'], $attributeValues->mode);
    }

    public function testGetPropertyAttributeWithoutDefinedAttributes()
    {
        $reflection = new \ReflectionClass(SimpleClass::class);
        $nameProperty = $reflection->getProperty('foo');

        $attributeValues = $this->reader->getPropertyAttribute($nameProperty, PropertyTestAttribute::class);

        $this->assertNull($attributeValues);
    }

    public function testGetMethodAttributeWithDefinedAttributes()
    {
        $reflectionMethod = new \ReflectionMethod(ClassWithAttributes::class, 'getFoo');

        $attributeValues = $this->reader->getMethodAttribute($reflectionMethod, MethodTestAttribute::class);

        $this->assertEquals(444, $attributeValues->id);
        $this->assertEquals('CustomMethod', $attributeValues->name);
        $this->assertEquals(['custom' => 'CustomMethodMode'], $attributeValues->mode);
    }

    public function testGetMethodAttributeWithoutDefinedAttributes()
    {
        $reflectionMethod = new \ReflectionMethod(SimpleClass::class, 'getFoo');

        $attributeValues = $this->reader->getMethodAttribute($reflectionMethod, MethodTestAttribute::class);

        $this->assertNull($attributeValues);
    }
}
