<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use PHPUnit\Framework\TestCase;

class PropertyConfigBagTest extends TestCase
{
    private PropertyConfigBag $propertyConfigBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->propertyConfigBag = new PropertyConfigBag(['scope1' => ['key' => 'value']]);
    }

    public function testGetPropertyConfigForExistingScope(): void
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope1');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame(['key' => 'value'], $propertyConfig->getConfig());
    }

    public function testGetPropertyConfigForNotExistingScope(): void
    {
        $propertyConfig = $this->propertyConfigBag->getPropertyConfig('scope2');
        self::assertInstanceOf(PropertyConfigContainer::class, $propertyConfig);
        self::assertSame([], $propertyConfig->getConfig());
    }
}
