<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\ImportExport\Reader\TranslationReader;

class TranslationReaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextRegistry;

    /** @var TranslatorBagInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var StepExecution|\PHPUnit_Framework_MockObject_MockObject */
    protected $stepExecution;

    /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->translator = $this->getMock(TranslatorBagInterface::class);
        $this->stepExecution = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMock(ContextInterface::class);

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($this->stepExecution)
            ->willReturn($this->context);

        $this->reader = new TranslationReader($this->contextRegistry, $this->translator);
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
        $this->translator->expects($this->any())
            ->method('getCatalogue')
            ->will($this->returnValueMap($this->getCatalogueMap()));

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
                    'original_value' => 'domain1-locale1-message1',
                ],
            ],
            'offset1' => [
                'offset' => 1,
                'locale' => 'locale1',
                'expected' => [
                    'domain' => 'domain1',
                    'key' => 'key2',
                    'value' => 'domain1-default-message2',
                    'original_value' => '',
                ],
            ],
            'offset2' => [
                'offset' => 2,
                'locale' => 'locale1',
                'expected' => [
                    'domain' => 'domain2',
                    'key' => 'key1',
                    'value' => 'domain2-locale1-message1',
                    'original_value' => 'domain2-locale1-message1',
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
            [
                Translation::DEFAULT_LOCALE,
                new MessageCatalogue(Translation::DEFAULT_LOCALE, [
                    'domain1' => [
                        'key2' => 'domain1-default-message2',
                        'key1' => 'domain1-default-message1',
                    ],
                ])
            ],
            [
                'locale1',
                new MessageCatalogue(Translation::DEFAULT_LOCALE, [
                    'domain1' => [
                        'key1' => 'domain1-locale1-message1',
                    ],
                    'domain2' => [
                        'key1' => 'domain2-locale1-message1',
                    ],
                ])
            ],
        ];
    }
}
