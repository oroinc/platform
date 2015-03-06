<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Annotation;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAlias()
    {
        $annotation = new Layout([]);
        $this->assertEquals(Layout::ALIAS, $annotation->getAliasName());
    }

    public function testAllowArray()
    {
        $annotation = new Layout([]);
        $this->assertFalse($annotation->allowArray());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param string $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Layout([]);

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['blockThemes', ['blockTheme']],
            ['vars', ['var1', 'var2']],
            ['theme', 'theme'],
        ];
    }
}
