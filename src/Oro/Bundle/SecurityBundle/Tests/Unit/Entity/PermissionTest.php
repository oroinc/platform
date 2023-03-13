<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Component\Testing\ReflectionUtil;

class PermissionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Permission */
    private $object;

    protected function setUp(): void
    {
        $this->object = new Permission();
    }

    public function testConstructor()
    {
        $this->assertEquals(new ArrayCollection(), $this->object->getApplyToEntities());
        $this->assertEquals(new ArrayCollection(), $this->object->getExcludeEntities());
        $this->assertEquals(new ArrayCollection(), $this->object->getGroupNames());
    }

    public function testGetId()
    {
        $this->assertNull($this->object->getId());

        $testValue = 42;
        ReflectionUtil::setId($this->object, $testValue);
        $this->assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     */
    public function testSetGet(string $propertyName, mixed $value, mixed $defaultValue = null)
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

    /**
     * @dataProvider addRemoveDataProvider
     */
    public function testAddRemove(string $propertyName, string $getter, mixed $value, mixed $defaultValue = null)
    {
        $defaultValue = $defaultValue ?: new ArrayCollection();
        $adder = 'add' . ucfirst($propertyName);
        $remover = 'remove' . ucfirst($propertyName);

        $this->assertEquals($defaultValue, $this->object->$getter());
        $this->object->$adder($value);
        $this->assertEquals(new ArrayCollection([$value]), $this->object->$getter());
        $this->object->$remover($value);
        $this->assertEquals(new ArrayCollection(), $this->object->$getter());
    }

    public function setGetDataProvider(): array
    {
        return [
            'name' => [
                'propertyName' => 'name',
                'value' => 'test name',
            ],
            'label' => [
                'propertyName' => 'label',
                'value' => 'test label',
            ],
            'applyToAll' => [
                'propertyName' => 'applyToAll',
                'value' => 'false',
                'defaultValue' => true,
            ],
            'applyToEntities' => [
                'propertyName' => 'applyToEntities',
                'value' => new ArrayCollection(['Entity1', 'Entity2']),
                'defaultValue' => new ArrayCollection(),
            ],
            'excludeEntities' => [
                'propertyName' => 'excludeEntities',
                'value' => new ArrayCollection(['Entity1', 'Entity2']),
                'defaultValue' => new ArrayCollection(),
            ],
            'groupNames' => [
                'propertyName' => 'groupNames',
                'value' => ['group1', 'group2'],
                'defaultValue' => new ArrayCollection(),
            ],
        ];
    }

    public function addRemoveDataProvider(): array
    {
        return [
            'groupNames' => [
                'propertyName' => 'groupName',
                'getter' => 'getGroupNames',
                'value' => 'test',
            ],
            'applyToEntities' => [
                'propertyName' => 'applyToEntity',
                'getter' => 'getApplyToEntities',
                'value' => new PermissionEntity(),
            ],
            'excludeEntities' => [
                'propertyName' => 'excludeEntity',
                'getter' => 'getExcludeEntities',
                'value' => new PermissionEntity(),
            ],
        ];
    }
}
