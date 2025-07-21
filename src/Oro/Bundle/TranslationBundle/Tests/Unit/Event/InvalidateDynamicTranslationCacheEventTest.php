<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Event;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;
use PHPUnit\Framework\TestCase;

class InvalidateDynamicTranslationCacheEventTest extends TestCase
{
    public function testEvent(): void
    {
        $locales = ['en_US', 'en'];
        $event = new InvalidateDynamicTranslationCacheEvent($locales);
        $this->assertEquals($locales, $event->getLocales());
    }
}
