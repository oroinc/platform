<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationError;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizerInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationsSanitizer;

class TranslationsSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationMessageSanitizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translationMessageSanitizer;

    /** @var TranslationsSanitizer */
    private $sanitizer;

    protected function setUp(): void
    {
        $this->translationMessageSanitizer = $this->createMock(TranslationMessageSanitizerInterface::class);

        $this->sanitizer = new TranslationsSanitizer($this->translationMessageSanitizer);
    }

    public function testSanitizeTranslations(): void
    {
        $locale = 'en';
        $translations = [
            'messages' => [
                'null'                            => null,
                'empty'                           => '',
                'message without tags'            => 'foo',
                'message with non sanitized tags' => '<b>Hello</b>',
                'sanitized message'               => 'Hello <script>alert(1)</script>',
            ]
        ];

        $this->translationMessageSanitizer->expects(self::exactly(3))
            ->method('isMessageSanitizationRequired')
            ->willReturnCallback(function (string $message): bool {
                return str_contains($message, '<');
            });
        $this->translationMessageSanitizer->expects(self::exactly(2))
            ->method('sanitizeMessage')
            ->withConsecutive(
                ['<b>Hello</b>'],
                ['Hello <script>alert(1)</script>']
            )
            ->willReturnOnConsecutiveCalls(
                '<b>Hello</b>',
                'Hello '
            );

        self::assertEquals(
            [
                new TranslationMessageSanitizationError(
                    $locale,
                    'messages',
                    'sanitized message',
                    'Hello <script>alert(1)</script>',
                    'Hello '
                )
            ],
            $this->sanitizer->sanitizeTranslations($translations, $locale)
        );
    }
}
