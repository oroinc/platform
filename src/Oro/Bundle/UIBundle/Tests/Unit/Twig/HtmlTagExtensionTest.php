<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HtmlTagExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private HtmlTagHelper&MockObject $htmlTagHelper;
    private HtmlTagExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_ui.html_tag_helper', $this->htmlTagHelper)
            ->getContainer($this);

        $this->extension = new HtmlTagExtension($container);
    }

    public function testHtmlSanitize(): void
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

    public function testHtmlSanitizeBasicReturnsEmptyStringWhenNullInput(): void
    {
        $this->htmlTagHelper->expects(self::never())
            ->method('sanitize');

        self::assertEquals(
            '',
            self::callTwigFilter($this->extension, 'oro_html_sanitize_basic', [null])
        );
    }

    public function testHtmlSanitizeBasic(): void
    {
        $html = '<div><b>sample text</b></div>';
        $htmlSanitized = '<b>sample text</b>';

        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($html, 'basic', false)
            ->willReturn($htmlSanitized);

        self::assertEquals(
            $htmlSanitized,
            self::callTwigFilter($this->extension, 'oro_html_sanitize_basic', [$html])
        );
    }

    public function testHtmlStripTags(): void
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

    public function testHtmlEscape(): void
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
    public function testAttributeNamePurify(string $string, string $expected): void
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
