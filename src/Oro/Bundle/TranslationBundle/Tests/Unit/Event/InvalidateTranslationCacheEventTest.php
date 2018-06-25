<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Event;

use Oro\Bundle\TranslationBundle\Event\InvalidateTranslationCacheEvent;

class InvalidateTranslationCacheEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $locale = 'en';
        $event = new InvalidateTranslationCacheEvent($locale);
        $this->assertSame($locale, $event->getLocale());
    }
}
