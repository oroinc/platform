<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Psr\Log\LoggerInterface;

class RebuildTranslationCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $domainProvider;

    /** @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $jsTranslationDumper;

    /** @var DynamicAssetVersionManager|\PHPUnit\Framework\MockObject\MockObject */
    private $assetVersionManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var RebuildTranslationCacheProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->domainProvider = $this->createMock(TranslationDomainProvider::class);
        $this->jsTranslationDumper = $this->createMock(JsTranslationDumper::class);
        $this->assetVersionManager = $this->createMock(DynamicAssetVersionManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new RebuildTranslationCacheProcessor(
            $this->translator,
            $this->domainProvider,
            $this->jsTranslationDumper,
            $this->assetVersionManager,
            $this->logger
        );
    }

    public function testRebuildCache()
    {
        $this->domainProvider->expects(self::once())
            ->method('clearCache');
        $this->translator->expects(self::once())
            ->method('rebuildCache');
        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations');
        $this->assetVersionManager->expects(self::once())
            ->method('updateAssetVersion')
            ->with('translations');

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertTrue($this->processor->rebuildCache());
    }

    public function testRebuildCacheWhenClearTranslationDomainCacheFailed()
    {
        $exception = new \Exception('some error');

        $this->domainProvider->expects(self::once())
            ->method('clearCache')
            ->willThrowException($exception);
        $this->translator->expects(self::never())
            ->method('rebuildCache');
        $this->jsTranslationDumper->expects(self::never())
            ->method('dumpTranslations');
        $this->assetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to rebuild the translation cache.',
                [
                    'exception' => new \RuntimeException(
                        'The clearing of available translation domains cache failed.',
                        $exception->getCode(),
                        $exception
                    )
                ]
            );

        self::assertFalse($this->processor->rebuildCache());
    }

    public function testRebuildCacheWhenRebuildTranslationCacheFailed()
    {
        $exception = new \Exception('some error');

        $this->domainProvider->expects(self::once())
            ->method('clearCache');
        $this->translator->expects(self::once())
            ->method('rebuildCache')
            ->willThrowException($exception);
        $this->jsTranslationDumper->expects(self::never())
            ->method('dumpTranslations');
        $this->assetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to rebuild the translation cache.',
                [
                    'exception' => new \RuntimeException(
                        'The rebuilding of cached translation message catalogs failed.',
                        $exception->getCode(),
                        $exception
                    )
                ]
            );

        self::assertFalse($this->processor->rebuildCache());
    }

    public function testRebuildCacheWhenDumpJsTranslationsFailed()
    {
        $exception = new \Exception('some error');

        $this->domainProvider->expects(self::once())
            ->method('clearCache');
        $this->translator->expects(self::once())
            ->method('rebuildCache');
        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations')
            ->willThrowException($exception);
        $this->assetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to rebuild the translation cache.',
                [
                    'exception' => new \RuntimeException(
                        'The dumping of JS translations failed.',
                        $exception->getCode(),
                        $exception
                    )
                ]
            );

        self::assertFalse($this->processor->rebuildCache());
    }

    public function testRebuildCacheWhenUpdateTranslationAssetVersionFailed()
    {
        $exception = new \Exception('some error');

        $this->domainProvider->expects(self::once())
            ->method('clearCache');
        $this->translator->expects(self::once())
            ->method('rebuildCache');
        $this->jsTranslationDumper->expects(self::once())
            ->method('dumpTranslations');
        $this->assetVersionManager->expects(self::once())
            ->method('updateAssetVersion')
            ->with('translations')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to rebuild the translation cache.',
                [
                    'exception' => new \RuntimeException(
                        'The updating of the translations asset version failed.',
                        $exception->getCode(),
                        $exception
                    )
                ]
            );

        self::assertFalse($this->processor->rebuildCache());
    }
}
