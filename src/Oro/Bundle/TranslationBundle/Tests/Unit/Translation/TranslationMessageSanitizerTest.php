<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class TranslationMessageSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var TranslationMessageSanitizer */
    private $sanitizer;

    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->sanitizer = new TranslationMessageSanitizer($this->htmlTagHelper);
    }

    /**
     * @dataProvider isMessageSanitizationRequiredDataProvider
     */
    public function testIsMessageSanitizationRequired(string $message, bool $expected): void
    {
        self::assertSame($expected, $this->sanitizer->isMessageSanitizationRequired($message));
    }

    public function isMessageSanitizationRequiredDataProvider(): array
    {
        return [
            'empty'                => ['', false],
            'message without tags' => ['Just a message', false],
            'message with tags'    => ['<b>Hello</b>', true],
        ];
    }

    /**
     * @dataProvider sanitizeMessageDataProvider
     */
    public function testSanitizeMessage(
        string $message,
        string $messageToSanitize,
        string $sanitizedMessage,
        string $expectedMessage
    ): void {
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($messageToSanitize, 'default', self::isFalse())
            ->willReturn($sanitizedMessage);

        self::assertSame($expectedMessage, $this->sanitizer->sanitizeMessage($message));
    }

    public function sanitizeMessageDataProvider(): array
    {
        return [
            'empty'                                     => [
                '',
                '',
                '',
                ''
            ],
            'message without tags'                      => [
                'Just a message',
                'Just a message',
                'Just a message',
                'Just a message'
            ],
            'message with non sanitized tags'           => [
                '<b>Hello</b>',
                '<b>Hello</b>',
                '<b>Hello</b>',
                '<b>Hello</b>'
            ],
            'message with sanitized variables in attrs' => [
                'Just <a href="#" data-a1="{val1}" data-a1-s="{ val1s }"'
                . ' data-a2="{{val2}}" data-a2-s="{{ val2s }}" data-a3="%a3%">refresh</a>',
                'Just <a href="#" data-a1="{val1}" data-a1-s="{ val1s }"'
                . ' data-a2="{{val2}}" data-a2-s="{{ val2s }}" data-a3="%a3%">refresh</a>',
                'Just <a href="#" data-a1="%7Bval1%7D" data-a1-s="%7B%20val1s%20%7D" data-a2="%7B%7Bval2%7D%7D"'
                . ' data-a2-s="%7B%7B%20val2s%20%7D%7D" data-a3="%25a3%25">refresh</a>',
                'Just <a href="#" data-a1="{val1}" data-a1-s="{ val1s }" data-a2="{{val2}}" data-a2-s="{{ val2s }}"'
                . ' data-a3="%a3%">refresh</a>'
            ],
            'sanitized message'                         => [
                'Hello <> <script>alert(1)</script>',
                'Hello &lt;&gt; <script>alert(1)</script>',
                'Hello &lt;&gt;',
                'Hello &lt;&gt;'
            ],
            'empty sanitized message'                   => [
                '<script>alert(1)</script>',
                '<script>alert(1)</script>',
                '',
                ''
            ],
        ];
    }
}
