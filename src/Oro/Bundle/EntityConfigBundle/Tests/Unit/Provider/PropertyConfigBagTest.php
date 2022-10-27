<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class PropertyConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyConfigBag */
    private $propertyConfigBag;

    protected function setUp(): void
    {
        $this->propertyConfigBag = new PropertyConfigBag(['scope1' => ['key' => 'value']]);
    }

    public function testGetPropertyConfigForExistingScope()
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope1');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame(['key' => 'value'], $propertyConfig->getConfig());
    }

    public function testGetPropertyConfigForNotExistingScope()
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope2');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame([], $propertyConfig->getConfig());
    }
}
