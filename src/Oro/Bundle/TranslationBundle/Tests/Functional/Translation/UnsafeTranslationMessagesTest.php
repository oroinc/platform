<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Oro\Bundle\TranslationBundle\Translation\MessageCatalogueSanitizerInterface;

class UnsafeTranslationMessagesTest extends WebTestCase
{
    private RebuildTranslationCacheProcessor $cacheProcessor;
    private MessageCatalogueSanitizerInterface $catalogueSanitizer;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->cacheProcessor = $this->getContainer()->get('oro_translation.rebuild_translation_cache_processor');
        $this->catalogueSanitizer = $this->getContainer()->get('oro_translation.message_catalogue_sanitizer');
    }

    public function testMessages()
    {
        $this->cacheProcessor->rebuildCache();
        $this->catalogueSanitizer->getSanitizationErrors();

        $errors = [];
        foreach ($this->catalogueSanitizer->getSanitizationErrors() as $sanitizationError) {
            $errors[] = $sanitizationError->__toString();
        }
        // Compared with empty array to get ALL errors listed in test output
        $this->assertEquals([], $errors);
    }
}
