<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Entity\Permission;

class PermissionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Permission */
    protected $object;

    protected function setUp()
    {
        $this->object = new Permission();
    }

    protected function tearDown()
    {
        unset($this->object);
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
        $reflectionProperty = new \ReflectionProperty('Oro\Bundle\SecurityBundle\Entity\Permission', 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->object, $testValue);

        $this->assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     *
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $defaultValue
     */
    public function testSetGet($propertyName, $value, $defaultValue = null)
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
     * @return array
     */
    public function setGetDataProvider()
    {
        return [
            'name' => [
                'propertyName' => 'name',
                'testValue' => 'test name',
            ],
            'label' => [
                'propertyName' => 'label',
                'testValue' => 'test label',
            ],
            'applyToAll' => [
                'propertyName' => 'applyToAll',
                'testValue' => 'false',
                'defaultValue' => true,
            ],
            'applyToEntities' => [
                'propertyName' => 'applyToEntities',
                'testValue' => ['Entity1', 'Entity2'],
                'defaultValue' => new ArrayCollection(),
            ],
            'excludeEntities' => [
                'propertyName' => 'excludeEntities',
                'testValue' => ['Entity1', 'Entity2'],
                'defaultValue' => new ArrayCollection(),
            ],
            'groupNames' => [
                'propertyName' => 'groupNames',
                'testValue' => ['group1', 'group2'],
                'defaultValue' => new ArrayCollection(),
            ],
        ];
    }
}
