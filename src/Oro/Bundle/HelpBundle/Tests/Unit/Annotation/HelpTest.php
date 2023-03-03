<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Annotation;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\HelpBundle\Annotation\Help;

class HelpTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAlias()
    {
        $annotation = new Help([]);
        $this->assertEquals(Help::ALIAS, $annotation->getAliasName());
    }

    public function testAllowArray()
    {
        $annotation = new Help([]);
        $this->assertTrue($annotation->allowArray());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param string $value
     * @param string $optionKey
     */
    public function testSettersAndGetters($property, $value, $optionKey)
    {
        $obj = new Help([]);

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
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
