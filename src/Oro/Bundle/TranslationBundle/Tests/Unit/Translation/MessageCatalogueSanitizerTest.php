<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\MessageCatalogueSanitizer;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationError;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationErrorCollection;
use Oro\Bundle\TranslationBundle\Translation\TranslationsSanitizer;
use Symfony\Component\Translation\MessageCatalogueInterface;

class MessageCatalogueSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationsSanitizer|\PHPUnit\Framework\MockObject\MockObject */
    private $translationsSanitizer;

    /** @var TranslationMessageSanitizationErrorCollection */
    private $sanitizationErrorCollection;

    /** @var MessageCatalogueSanitizer */
    private $sanitizer;

    protected function setUp(): void
    {
        $this->translationsSanitizer = $this->createMock(TranslationsSanitizer::class);
        $this->sanitizationErrorCollection = new TranslationMessageSanitizationErrorCollection();

        $this->sanitizer = new MessageCatalogueSanitizer(
            $this->translationsSanitizer,
            $this->sanitizationErrorCollection
        );
    }

    public function testSanitizeCatalogue(): void
    {
        $locale = 'en';
        $translations = [
            'messages'   => [
                'message'           => 'Hello',
                'sanitized message' => 'Hello <script>alert(1)</script>',
            ],
            'jsmessages' => [
                'foo' => 'Foo JS',
            ]
        ];
        $sanitizationError = new TranslationMessageSanitizationError(
            $locale,
            'messages',
            'sanitized message',
            $translations['messages']['sanitized message'],
            'Hello '
        );

        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue->expects(self::any())
            ->method('getLocale')
            ->willReturn($locale);
        $catalogue->expects(self::once())
            ->method('all')
            ->willReturn($translations);
        $catalogue->expects(self::once())
            ->method('replace')
            ->with(
                [
                    'message'           => 'Hello',
                    'sanitized message' => 'Hello ',
                ],
                'messages'
            );

        $this->translationsSanitizer->expects(self::once())
            ->method('sanitizeTranslations')
            ->with($translations, $locale)
            ->willReturn([$sanitizationError]);

        $this->sanitizer->sanitizeCatalogue($catalogue);

        self::assertEquals(
            [$sanitizationError],
            $this->sanitizationErrorCollection->all()
        );
    }
}
