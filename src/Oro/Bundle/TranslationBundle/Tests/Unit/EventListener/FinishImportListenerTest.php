<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Event\FinishImportEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TranslationBundle\EventListener\FinishImportListener;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinishImportListenerTest extends TestCase
{
    private JsTranslationDumper&MockObject $translationDumper;
    private DynamicAssetVersionManager&MockObject $dynamicAssetVersionManager;
    private FinishImportListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->translationDumper = $this->createMock(JsTranslationDumper::class);
        $this->dynamicAssetVersionManager = $this->createMock(DynamicAssetVersionManager::class);

        $this->listener = new FinishImportListener($this->translationDumper, $this->dynamicAssetVersionManager);
    }

    /**
     * @dataProvider testOnFinishImportDataProvider
     */
    public function testOnFinishImport(int $jobId, string $alias, string $type, array $options): void
    {
        $this->translationDumper->expects(self::once())
            ->method('dumpTranslations')
            ->with(['en']);

        $this->dynamicAssetVersionManager->expects(self::once())
            ->method('updateAssetVersion')
            ->with('translations');

        $event = new FinishImportEvent($jobId, $alias, $type, $options);

        $this->listener->onFinishImport($event);
    }

    public function testOnFinishImportDataProvider(): array
    {
        return [
            'Import with reset strategy' => [
                'jobId' => 1,
                'processorAlias' => 'oro_translation_translation.reset',
                'type' => ProcessorRegistry::TYPE_IMPORT,
                'options' => ['language_code' => 'en']
            ],
            'Import with add or replace strategy' => [
                'jobId' => 1,
                'processorAlias' => 'oro_translation_translation.add_or_replace',
                'type' => ProcessorRegistry::TYPE_IMPORT,
                'options' => ['language_code' => 'en']
            ]
        ];
    }

    /**
     * @dataProvider testOnFinishImportWithUnsupportedDataDataProvider
     */
    public function testOnFinishImportWithUnsupportedData(int $jobId, string $alias, string $type, array $options): void
    {
        $this->translationDumper->expects(self::never())
            ->method('dumpTranslations');

        $this->dynamicAssetVersionManager->expects(self::never())
            ->method('updateAssetVersion');

        $event = new FinishImportEvent($jobId, $alias, $type, $options);

        $this->listener->onFinishImport($event);
    }

    public function testOnFinishImportWithUnsupportedDataDataProvider(): array
    {
        return [
            'Import with unsupported alias' => [
                'jobId' => 1,
                'processorAlias' => 'invalid alias',
                'type' => ProcessorRegistry::TYPE_IMPORT,
                'options' => ['language_code' => 'en']
            ],
            'Import with unsupported type' => [
                'jobId' => 1,
                'processorAlias' => 'oro_translation_translation.reset',
                'type' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                'options' => ['language_code' => 'en']
            ],
            'Import with unsupported options' => [
                'jobId' => 1,
                'processorAlias' => 'oro_translation_translation.reset',
                'type' => ProcessorRegistry::TYPE_IMPORT,
                'options' => []
            ],
        ];
    }
}
