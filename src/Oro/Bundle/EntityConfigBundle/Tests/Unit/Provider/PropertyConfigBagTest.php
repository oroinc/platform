<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class PropertyConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyConfigBag */
    protected $propertyConfigBag;

    protected function setUp()
    {
        $this->propertyConfigBag = new PropertyConfigBag(['scope1' => ['key' => 'value']]);
    }

    public function testGetPropertyConfigForExistingScope()
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope1');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame(['key' => 'value'], $propertyConfig->getConfig());
        $configObjects = self::getObjectAttribute($this->propertyConfigBag, 'configObjects');
        self::assertArrayHasKey('scope1', $configObjects);
        self::assertSame($propertyConfig, $configObjects['scope1']);
    }

    public function testGetPropertyConfigForNotExistingScope()
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope2');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame([], $propertyConfig->getConfig());
        $configObjects = self::getObjectAttribute($this->propertyConfigBag, 'configObjects');
        self::assertArrayHasKey('scope2', $configObjects);
        self::assertSame($propertyConfig, $configObjects['scope2']);
    }
}
