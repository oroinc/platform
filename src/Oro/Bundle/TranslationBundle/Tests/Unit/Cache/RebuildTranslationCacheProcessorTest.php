<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RebuildTranslationCacheProcessorTest extends TestCase
{
    private Translator&MockObject $translator;
    private TranslationDomainProvider&MockObject $domainProvider;
    private JsTranslationDumper&MockObject $jsTranslationDumper;
    private DynamicAssetVersionManager&MockObject $assetVersionManager;
    private LoggerInterface&MockObject $logger;
    private RebuildTranslationCacheProcessor $processor;

    #[\Override]
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

    public function testRebuildCache(): void
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

    public function testRebuildCacheWhenClearTranslationDomainCacheFailed(): void
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

    public function testRebuildCacheWhenRebuildTranslationCacheFailed(): void
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

    public function testRebuildCacheWhenDumpJsTranslationsFailed(): void
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

    public function testRebuildCacheWhenUpdateTranslationAssetVersionFailed(): void
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
