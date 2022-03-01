<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\MessageCatalogueSanitizer;
use Oro\Bundle\TranslationBundle\Translation\SanitizationErrorInformation;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Translation\MessageCatalogueInterface;

class MessageCatalogueSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /** HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private HtmlTagHelper $htmlTagHelper;

    private MessageCatalogueSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->sanitizer = new MessageCatalogueSanitizer($this->htmlTagHelper);
    }

    public function testSanitizeCatalogue(): void
    {
        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue->expects(self::any())
            ->method('getLocale')
            ->willReturn('en');

        $messages = [
            'message_without_tags' => 'Just a message',
            'message_with_unsanitized_tags' => '<b>Hello</b>',
            'message_with_sanitized_variables_in_attrs' => 'Just <a href="#" data-a1="{val1}" data-a1-s="{ val1s }"'
                . ' data-a2="{{val2}}" data-a2-s="{{ val2s }}" data-a3="%a3%">refresh</a>',
            'sanitized_message' => 'Hello <> <script>alert(1)</script>'
        ];
        $catalogue->expects(self::once())
            ->method('all')
            ->willReturn(['messages' => $messages]);

        $this->htmlTagHelper->expects(self::exactly(3))
            ->method('sanitize')
            ->withConsecutive(
                [$messages['message_with_unsanitized_tags'], 'default', false],
                [$messages['message_with_sanitized_variables_in_attrs'], 'default', false],
                ['Hello &lt;&gt; <script>alert(1)</script>', 'default', false]
            )
            ->willReturnOnConsecutiveCalls(
                '<b>Hello</b>',
                'Just <a href="#" data-a1="%7Bval1%7D" data-a1-s="%7B%20val1s%20%7D" data-a2="%7B%7Bval2%7D%7D"'
                . ' data-a2-s="%7B%7B%20val2s%20%7D%7D" data-a3="%25a3%25">refresh</a>',
                'Hello &lt;&gt;'
            );

        $this->sanitizer->sanitizeCatalogue($catalogue);
        $errors = $this->sanitizer->getSanitizationErrors();

        self::assertNotEmpty($errors);
        self::assertCount(1, $errors);
        self::assertEquals(
            [
                new SanitizationErrorInformation(
                    'en',
                    'messages',
                    'sanitized_message',
                    'Hello <> <script>alert(1)</script>',
                    'Hello &lt;&gt;'
                ),
            ],
            $errors
        );
    }
}
