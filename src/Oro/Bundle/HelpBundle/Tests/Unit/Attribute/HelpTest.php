<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Attribute;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\HelpBundle\Attribute\Help;
use PHPUnit\Framework\TestCase;

class HelpTest extends TestCase
{
    public function testGetAlias(): void
    {
        $attribute = new Help();
        $this->assertEquals(Help::ALIAS, $attribute->getAliasName());
    }

    public function testAllowArray(): void
    {
        $attribute = new Help();
        $this->assertTrue($attribute->allowArray());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param string $value
     * @param string $optionKey
     */
    public function testSettersAndGetters($property, $value, $optionKey): void
    {
        $obj = new Help(...[$property => $value]);

        $accessor = PropertyAccess::createPropertyAccessor();
        $this->assertEquals($value, $accessor->getValue($obj, $property));
        $this->assertEquals([$optionKey => $value], $obj->getConfigurationArray());
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['controllerAlias', 'controller', 'controller'],
            ['actionAlias', 'action', 'action'],
            ['bundleAlias', 'bundle', 'bundle'],
            ['vendorAlias', 'vendor', 'vendor'],
            ['link', 'link', 'link'],
            ['prefix', 'prefix', 'prefix'],
            ['server', 'server', 'server'],
            ['uri', 'uri', 'uri'],
        ];
    }
}
