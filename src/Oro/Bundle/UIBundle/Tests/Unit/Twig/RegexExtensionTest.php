<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\RegexExtension;

class RegexExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegexExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new RegexExtension();
    }

    public function testGetName()
    {
        $this->assertEquals(RegexExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFilters();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = current($functions);
        $this->assertInstanceOf('\Twig_SimpleFilter', $function);
        $this->assertEquals('oro_preg_replace', $function->getName());
        $this->assertEquals([$this->extension, 'pregReplace'], $function->getCallable());
    }

    /**
     * @param string $expected
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     *
     * @dataProvider addDataProvider
     */
    public function testRegex($expected, $pattern, $replacement, $subject)
    {
        $this->assertEquals($expected, $this->extension->pregReplace($pattern, $replacement, $subject));
    }

    /**
     * @return array
     */
    public function addDataProvider()
    {
        return [
            'pattern 1' => [
                'expected' => 'aaaaa aaaaaabbccccccccaaaaad d d d d d d ddde',
                'pattern'   => '/(\s){2,}/',
                'replacement'   => '$1',
                'subject'   => 'aaaaa   aaaaaabbccccccccaaaaad d d d   d      d d ddde',
            ],
            'pattern 2' => [
                'expected' => '-asd-',
                'pattern'   => '/(-){2,}/',
                'replacement'   => '$1',
                'subject'   => '------------asd----------',
            ],
        ];
    }
}
