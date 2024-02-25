<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Attribute;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LayoutBundle\Attribute\Layout;

class LayoutTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAlias()
    {
        $attribute = new Layout();
        $this->assertEquals('layout', $attribute->getAliasName());
    }

    public function testAllowArray()
    {
        $attribute = new Layout();
        $this->assertFalse($attribute->allowArray());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, string|array $value)
    {
        $obj = new Layout();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testConstructor(string $property, string|array $value, Layout $obj)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['blockThemes', ['blockTheme1', 'blockTheme2'], new Layout(blockThemes: ['blockTheme1', 'blockTheme2'])],
            ['blockThemes', 'blockTheme', new Layout(blockThemes: 'blockTheme')],
            ['vars', ['var1', 'var2'], new Layout(vars: ['var1', 'var2'])],
            ['theme', 'theme', new Layout(theme: 'theme')],
            ['action', 'action', new Layout(action: 'action')]
        ];
    }

    public function testSingleBlockThemeSetter()
    {
        $obj = new Layout(blockThemes: 'blockTheme');
        $this->assertEquals('blockTheme', $obj->getBlockThemes());
    }

    public function testSetValueShouldSetAction()
    {
        $obj = new Layout();
        $obj->setValue('action');
        $this->assertEquals('action', $obj->getAction());
    }
}
