<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class PermissionEntityTest extends TestCase
{
    private PermissionEntity $object;

    #[\Override]
    protected function setUp(): void
    {
        $this->object = new PermissionEntity();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->object->getId());

        $testValue = 42;
        ReflectionUtil::setId($this->object, $testValue);
        $this->assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     */
    public function testSetGet(string $propertyName, mixed $value, mixed $defaultValue = null): void
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = 'get' . ucfirst($propertyName);
        if (!method_exists($this->object, $getter)) {
            $getter = 'is' . ucfirst($propertyName);
        }

        $this->assertEquals($defaultValue, $this->object->$getter());
        $this->assertSame($this->object, $this->object->$setter($value));
        $this->assertSame($value, $this->object->$getter());
    }

    public function setGetDataProvider(): array
    {
        return [
            'name' => [
                'propertyName' => 'name',
                'value' => 'test name',
            ],
        ];
    }
}
