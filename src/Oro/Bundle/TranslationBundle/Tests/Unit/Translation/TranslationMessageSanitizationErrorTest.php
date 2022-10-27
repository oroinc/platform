<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationError;

class TranslationMessageSanitizationErrorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $error = new TranslationMessageSanitizationError('en', 'messages', 'key1', 'message1', 'sanitized message 1');
        $this->assertEquals('en', $error->getLocale());
        $this->assertEquals('messages', $error->getDomain());
        $this->assertEquals('key1', $error->getMessageKey());
        $this->assertEquals('message1', $error->getOriginalMessage());
        $this->assertEquals('sanitized message 1', $error->getSanitizedMessage());

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'locale' => 'en',
                'domain' => 'messages',
                'messageKey' => 'key1',
                'originalMessage' => 'message1',
                'sanitizedMessage' => 'sanitized message 1'
            ]),
            $error->__toString()
        );
    }
}
