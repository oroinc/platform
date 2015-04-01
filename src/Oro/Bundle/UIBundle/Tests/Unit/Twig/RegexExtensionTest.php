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
     * @param string $subject
     * @param string $pattern
     * @param string $replacement
     * @param string $limit
     *
     * @dataProvider addDataProvider
     */
    public function testRegex($expected, $subject, $pattern, $replacement, $limit)
    {
        $this->assertEquals($expected, $this->extension->pregReplace($subject, $pattern, $replacement, $limit));
    }

    /**
     * @return array
     */
    public function addDataProvider()
    {
        return [
            'pattern 1' => [
                'expected' => 'aaaaa aaaaaabbccccccccaaaaad d d d d d d ddde',
                'subject'   => 'aaaaa   aaaaaabbccccccccaaaaad d d d   d      d d ddde',
                'pattern'   => '/(\s){2,}/',
                'replacement'   => '$1',
                'limit' => -1
            ],
            'pattern 2' => [
                'expected' => '-asd-',
                'subject'   => '------------asd----------',
                'pattern'   => '/(-){2,}/',
                'replacement'   => '$1',
                'limit' => -1,
            ],
            'pattern 3' => [
                'expected' => '-asd-',
                'subject'   => '-asd----------',
                'pattern'   => '/(-){2,}/',
                'replacement'   => '$1',
                'limit' => 1,
            ],
        ];
    }
}
