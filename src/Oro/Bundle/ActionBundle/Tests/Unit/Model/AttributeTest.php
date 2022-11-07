<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\EntityParameterInterface;
use Oro\Bundle\ActionBundle\Model\ParameterInterface;

class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testGettersAndSetters(string $property, string|array $value): void
    {
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);
        $obj = new Attribute();
        $this->assertInstanceOf(
            Attribute::class,
            $obj->$setter($value)
        );
        $this->assertEquals($value, $obj->$getter());
    }

    public function propertiesDataProvider(): array
    {
        return [
            'name' => ['name', 'test'],
            'label' => ['label', 'test'],
            'type' => ['type', 'string'],
            'options' => ['options', ['key' => 'value']],
            'default' => ['default', ['sample_value']],
        ];
    }

    public function testGetSetOption(): void
    {
        $obj = new Attribute();
        $obj->setOptions(['key' => 'test']);
        $this->assertEquals('test', $obj->getOption('key'));
        $obj->setOption('key2', 'test2');
        $this->assertEquals(['key' => 'test', 'key2' => 'test2'], $obj->getOptions());
        $obj->setOption('key', 'test_changed');
        $this->assertEquals('test_changed', $obj->getOption('key'));
    }

    public function testEntityAclAllowed(): void
    {
        $attribute = new Attribute();

        $this->assertTrue($attribute->isEntityUpdateAllowed());
        $this->assertTrue($attribute->isEntityDeleteAllowed());

        $attribute->setEntityAcl(['update' => false, 'delete' => false]);
        $this->assertFalse($attribute->isEntityUpdateAllowed());
        $this->assertFalse($attribute->isEntityDeleteAllowed());

        $attribute->setEntityAcl(['update' => true, 'delete' => true]);
        $this->assertTrue($attribute->isEntityUpdateAllowed());
        $this->assertTrue($attribute->isEntityDeleteAllowed());
    }

    public function testInstanceAndInternalType(): void
    {
        $attribute = new Attribute();
        $this->assertInstanceOf(EntityParameterInterface::class, $attribute);
        $this->assertInstanceOf(ParameterInterface::class, $attribute);

        $this->assertEquals('attribute', $attribute->getInternalType());
    }
}
