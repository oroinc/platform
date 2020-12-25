<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandler;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;

class RebuildTranslationCacheHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RebuildTranslationCacheProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $rebuildTranslationCacheProcessor;

    /** @var RebuildTranslationCacheHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->rebuildTranslationCacheProcessor = $this->createMock(RebuildTranslationCacheProcessor::class);

        $this->handler = new RebuildTranslationCacheHandler($this->rebuildTranslationCacheProcessor);
    }

    public function testRebuildCacheWhenSuccess()
    {
        $this->rebuildTranslationCacheProcessor->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(true);

        $result = $this->handler->rebuildCache();
        self::assertTrue($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }

    public function testRebuildCacheWhenFailed()
    {
        $this->rebuildTranslationCacheProcessor->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(false);

        $result = $this->handler->rebuildCache();
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->getFailureMessage());
    }
}
