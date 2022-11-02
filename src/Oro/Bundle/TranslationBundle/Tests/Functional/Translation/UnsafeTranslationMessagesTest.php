<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationErrorCollection;

class UnsafeTranslationMessagesTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testMessages()
    {
        /** @var RebuildTranslationCacheProcessor $cacheProcessor */
        $cacheProcessor = self::getContainer()->get('oro_translation.rebuild_translation_cache_processor');
        $cacheProcessor->rebuildCache();

        /** @var TranslationMessageSanitizationErrorCollection $sanitizationErrorCollection */
        $sanitizationErrorCollection = self::getContainer()
            ->get('oro_translation.translation_message_sanitization_errors');
        $this->assertSame([], $sanitizationErrorCollection->all());
    }
}
