<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\ImportExport\Reader\TranslationReader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageRepository;

    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    protected $stepExecution;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var TranslationReader */
    protected $reader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->languageRepository = $this->createMock(LanguageRepository::class);
        $this->stepExecution = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->createMock(ContextInterface::class);

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($this->stepExecution)
            ->willReturn($this->context);

        $this->reader = new TranslationReader($this->contextRegistry, $this->languageRepository);
        $this->reader->setStepExecution($this->stepExecution);
    }

    /**
     * @param int $offset
     * @param string $locale
     * @param array|null $expectedData
     *
     * @dataProvider readProvider
     */
    public function testRead($offset, $locale, array $expectedData = null)
    {
        $this->languageRepository->expects($this->at(0))
            ->method('getTranslationsForExport')
            ->with(Translator::DEFAULT_LOCALE)
            ->willReturn($this->getCatalogueEnMap());

        $this->languageRepository->expects($this->at(1))
            ->method('getTranslationsForExport')
            ->with($locale)
            ->willReturn($this->getCatalogueMap());

        $this->stepExecution->expects($this->once())->method('getReadCount')->willReturn($offset);
        $this->context->expects($this->once())->method('getOption')->with('language_code')->willReturn('locale1');

        $this->assertEquals($expectedData, $this->reader->read());
    }

    /**
     * @return array
     */
    public function readProvider()
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

    /**
     * @return array
     */
    protected function getCatalogueMap()
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

    /**
     *
     */
    private function getCatalogueEnMap()
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
