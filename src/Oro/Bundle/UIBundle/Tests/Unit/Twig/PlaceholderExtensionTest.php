<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tests\Unit\Twig\Template\TestJSON;
use Oro\Bundle\UIBundle\Tests\Unit\Twig\Template\TestHTML;
use Oro\Bundle\UIBundle\Tests\Unit\Twig\Template\TestJS;
use Oro\Bundle\UIBundle\Twig\PlaceholderExtension;

class PlaceholderExtensionTest extends \PHPUnit_Framework_TestCase
{
    const VALUE = 'string';
    const DELIMITER = ',';

    /**
     * @var PlaceholderExtension
     */
    protected $extension;
    /**
     * @var Twig_Environment
     */
    protected $twig;

    protected function setUp()
    {
        $this->twig = $this
            ->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $placehoders = [
            'placeholder' => [
                'items' => [
                    ['template' => 'template'],
                    ['template' => 'template']
                ]
            ]
        ];

        $this->extension = new PlaceholderExtension($this->twig, $placehoders);
    }

    public function testRenderPlaceholders()
    {
        $this->twig
            ->expects($this->exactly(2))
            ->method('render')
            ->will($this->returnValue(self::VALUE));

        $result = $this->extension->renderPlaceholders('placeholder');
        $this->assertEquals(str_repeat(self::VALUE, 2), $result);
    }


    public function testRenderPlaceholdersWithDelimiter()
    {
        $this->twig
            ->expects($this->exactly(2))
            ->method('render')
            ->will($this->returnValue(self::VALUE));

        $result = $this->extension->renderPlaceholders('placeholder', [], self::DELIMITER);
        $this->assertEquals(implode(self::DELIMITER, [self::VALUE, self::VALUE]), $result);
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('placeholder', $this->extension->getFunctions());
    }

    public function testGetName()
    {
        $this->assertEquals(PlaceholderExtension::EXTENSION_NAME, $this->extension->getName());
    }
}
