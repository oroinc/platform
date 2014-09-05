<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\ContentExtension;

class ContentExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentProviderManager;

    /**
     * @var ContentExtension
     */
    protected $extension;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->contentProviderManager = $this
            ->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ContentExtension($this->contentProviderManager);
    }

    public function testName()
    {
        $this->assertEquals('oro_ui.content', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_Function_Method $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_Function_Method', $function);
        }
    }

    /**
     * @dataProvider contentDataProvider
     * @param array $content
     * @param array|null $additionalContent
     * @param array|null $keys
     * @param array $expected
     */
    public function testGetContent($content, $additionalContent, $keys, $expected)
    {
        $this->contentProviderManager->expects($this->once())
            ->method('getContent')
            ->with($keys)
            ->will($this->returnValue($content));
        $this->assertEquals($expected, $this->extension->getContent($additionalContent, $keys));
    }

    public function contentDataProvider()
    {
        return array(
            array(
                'content' => array('b' => 'c'),
                'additionalContent' => array('a' => 'b'),
                'keys' => array('a', 'b', 'c'),
                'expected' => array('a' => 'b', 'b' => 'c')
            ),
            array(
                'content' => array('b' => 'c'),
                'additionalContent' => null,
                'keys' => null,
                'expected' => array('b' => 'c')
            ),
        );
    }
}
