<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheResult;

class RebuildTranslationCacheResultTest extends \PHPUnit\Framework\TestCase
{
    public function testSuccessResult()
    {
        $result = new RebuildTranslationCacheResult(true);
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testFailureResult()
    {
        $result = new RebuildTranslationCacheResult(false, 'some error');
        self::assertFalse($result->isSuccessful());
        self::assertEquals('some error', $result->getFailureMessage());
    }
}
