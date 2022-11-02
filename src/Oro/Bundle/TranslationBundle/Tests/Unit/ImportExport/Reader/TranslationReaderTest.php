<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Reader;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\ImportExport\Reader\TranslationReader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $languageRepository;

    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecution;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var TranslationReader */
    private $reader;

    protected function setUp(): void
    {
        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $this->stepExecution = $this->createMock(StepExecution::class);
        $this->context = $this->createMock(ContextInterface::class);

        $contextRegistry = $this->createMock(ContextRegistry::class);
        $contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($this->stepExecution)
            ->willReturn($this->context);

        $this->reader = new TranslationReader($contextRegistry, $this->languageRepository);
        $this->reader->setStepExecution($this->stepExecution);
    }

    /**
     * @dataProvider readProvider
     */
    public function testRead(int $offset, string $locale, array $expectedData = null)
    {
        $this->languageRepository->expects($this->exactly(2))
            ->method('getTranslationsForExport')
            ->willReturnMap([
                [Translator::DEFAULT_LOCALE, $this->getCatalogueEnMap()],
                [$locale, $this->getCatalogueMap()]
            ]);

        $this->stepExecution->expects($this->once())
            ->method('getReadCount')
            ->willReturn($offset);
        $this->context->expects($this->once())
            ->method('getOption')
            ->with('language_code')
            ->willReturn('locale1');

        $this->assertEquals($expectedData, $this->reader->read());
    }

    public function readProvider(): array
    {
        return [
            'offset0' => [
                'offset' => 0,
                'locale' => 'locale1',
                'expected' => [
                    'domain' => 'domain1',
                    'key' => 'key1',
                    'value' => 'domain1-locale1-message1',
                    'english_translation' => 'domain1-en-message1',
                    'has_translation' => 1,
                ],
            ],
            'offset1' => [
                'offset' => 1,
                'locale' => 'locale1',
                'expected' => [
                    'domain' => 'domain1',
                    'key' => 'key2',
                    'value' => 'domain1-default-message2',
                    'english_translation' => '',
                    'has_translation' => 1,
                ],
            ],
            'offset2' => [
                'offset' => 2,
                'locale' => 'locale1',
                'expected' => [
                    'domain' => 'domain2',
                    'key' => 'key1',
                    'value' => 'domain2-locale1-message1',
                    'english_translation' => 'domain2-en-message1',
                    'has_translation' => 1,
                ],
            ],
            'offset3' => [
                'offset' => 3,
                'locale' => 'locale1',
                'expected' => null,
            ],
        ];
    }

    private function getCatalogueMap(): array
    {
        return [
            0 => [
                'domain' => 'domain1',
                'key' => 'key1',
                'value' => 'domain1-locale1-message1',
                'has_translation' => 1,
            ],
            1 => [
                'domain' => 'domain1',
                'key' => 'key2',
                'value' => 'domain1-default-message2',
                'has_translation' => 1,
            ],
            2 => [
                'domain' => 'domain2',
                'key' => 'key1',
                'value' => 'domain2-locale1-message1',
                'has_translation' => 1,
            ],
        ];
    }

    private function getCatalogueEnMap(): array
    {
        return [
            0 => [
                'domain' => 'domain1',
                'key' => 'key1',
                'value' => 'domain1-en-message1',
                'has_translation' => 1,
            ],
            1 => [
                'domain' => 'domain1',
                'key' => 'key2',
                'value' => '',
                'has_translation' => 1,
            ],
            2 => [
                'domain' => 'domain2',
                'key' => 'key1',
                'value' => 'domain2-en-message1',
                'has_translation' => 1,
            ],
        ];
    }
}
