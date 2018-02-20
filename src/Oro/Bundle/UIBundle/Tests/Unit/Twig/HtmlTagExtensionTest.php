<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class HtmlTagExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $htmlTagHelper;

    /* @var HtmlTagExtension */
    protected $extension;

    protected function setUp()
    {
        $this->htmlTagHelper = $this->getMockBuilder(HtmlTagHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_ui.html_tag_helper', $this->htmlTagHelper)
            ->getContainer($this);

        $this->extension = new HtmlTagExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui.html_tag', $this->extension->getName());
    }

    public function testHtmlTagTrim()
    {
        $tags = ['script', 'style'];
        $html = <<<EOF
<iframe src="https://www.somehost"></iframe><script>alert('Some script')</script><style type="text/css">
   h1 { 
    font-size: 120%; 
   }
</style><script>alert('Some script again!')</script>
EOF;
        $expectedResult = '<iframe src="https://www.somehost"></iframe>';
        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_html_tag_trim', [$html, $tags])
        );
    }

    /**
     * @dataProvider attributeDataProvider
     * @param string $string
     * @param string $expected
     */
    public function testAttributeNamePurify($string, $expected)
    {
        $this->assertSame(
            $expected,
            self::callTwigFilter($this->extension, 'oro_attribute_name_purify', [$string])
        );
    }

    /**
     * @return array
     */
    public function attributeDataProvider()
    {
        return [
            [' onclick=alert(1)', 'onclickalert1'],
            ['тест\/"\'attribute_some-123 HelloFake%20attr', 'attribute_some-123HelloFake20attr']
        ];
    }
}
