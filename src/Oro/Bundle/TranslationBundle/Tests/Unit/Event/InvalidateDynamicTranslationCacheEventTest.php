<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Event;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;

class InvalidateDynamicTranslationCacheEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $locales = ['en_US', 'en'];
        $event = new InvalidateDynamicTranslationCacheEvent($locales);
        $this->assertEquals($locales, $event->getLocales());
    }
}
