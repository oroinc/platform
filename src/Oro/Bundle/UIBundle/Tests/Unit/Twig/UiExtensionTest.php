<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\UiExtension;

class UiExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Oro\Bundle\UIBundle\Twig\UiExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new UiExtension(array(), 'test_class');
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui', $this->extension->getName());
    }

    public function testGetTokenParsers()
    {
        $parsers = $this->extension->getTokenParsers();
        $this->assertTrue($parsers[0] instanceof \Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser);
    }

    public function testGetFilters()
    {
        $this->assertArrayHasKey('trimString', $this->extension->getFilters());
    }

    /**
     * @dataProvider testStrings
     */
    public function testTrimString($inputString, $symbolCount ,$expectedString)
    {
        $this->assertEquals($expectedString, $this->extension->trimString($inputString, $symbolCount));
    }

    public function testStrings()
    {
        return [
            [
                'Lorem ipsum dolor sit amet',
                2,
                'Lo...'
            ],
            [
                'Lorem ipsum dolor sit amet',
                10,
                'Lorem ipsu...'
            ],
            [
                'Lorem ipsum dolor sit amet',
                50,
                'Lorem ipsum dolor sit amet'
            ],
        ];
    }
}
