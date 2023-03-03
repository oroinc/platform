<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Annotation;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

class LayoutTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAlias()
    {
        $annotation = new Layout([]);
        $this->assertEquals('layout', $annotation->getAliasName());
    }

    public function testAllowArray()
    {
        $annotation = new Layout([]);
        $this->assertFalse($annotation->allowArray());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, string|array $value)
    {
        $obj = new Layout([]);

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testConstructor(string $property, string|array $value)
    {
        $obj = new Layout([$property => $value]);

        $accessor = PropertyAccess::createPropertyAccessor();
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['blockThemes', ['blockTheme1', 'blockTheme2']],
            ['blockThemes', 'blockTheme'],
            ['vars', ['var1', 'var2']],
            ['theme', 'theme'],
            ['action', 'action']
        ];
    }

    public function testSingleBlockThemeSetter()
    {
        $obj = new Layout(['blockTheme' => 'blockTheme']);
        $this->assertEquals('blockTheme', $obj->getBlockThemes());
    }

    public function testSetValueShouldSetAction()
    {
        $obj = new Layout([]);
        $obj->setValue('action');
        $this->assertEquals('action', $obj->getAction());
    }
}
