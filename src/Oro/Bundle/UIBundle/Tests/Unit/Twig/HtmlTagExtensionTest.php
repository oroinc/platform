<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class HtmlTagExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /* @var HtmlTagExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_ui.html_tag_helper', $this->htmlTagHelper)
            ->getContainer($this);

        $this->extension = new HtmlTagExtension($container);
    }

    public function testHtmlSanitize()
    {
        $html = '<html>HTML</html>';

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($html)
            ->willReturn('HTML');

        $this->assertEquals(
            'HTML',
            self::callTwigFilter($this->extension, 'oro_html_sanitize', [$html])
        );
    }

    public function testHtmlStripTags()
    {
        $html = '<html>HTML</html>';

        $this->htmlTagHelper->expects($this->once())
            ->method('stripTags')
            ->with($html)
            ->willReturn('HTML');

        $this->assertEquals(
            'HTML',
            self::callTwigFilter($this->extension, 'oro_html_strip_tags', [$html])
        );
    }

    public function testHtmlEscape()
    {
        $html = '<div>HTML</div><script type="text/javascript"></script>';

        $this->htmlTagHelper->expects($this->once())
            ->method('escape')
            ->with($html)
            ->willReturn('<div>HTML</div>');
        $this->assertEquals(
            '<div>HTML</div>',
            self::callTwigFilter($this->extension, 'oro_html_escape', [$html])
        );
    }

    /**
     * @dataProvider attributeDataProvider
     */
    public function testAttributeNamePurify(string $string, string $expected)
    {
        $this->assertSame(
            $expected,
            self::callTwigFilter($this->extension, 'oro_attribute_name_purify', [$string])
        );
    }

    public function attributeDataProvider(): array
    {
        return [
            [' onclick=alert(1)', 'onclickalert1'],
            ['тест\/"\'attribute_some-123 HelloFake%20attr', 'attribute_some-123HelloFake20attr']
        ];
    }
}
