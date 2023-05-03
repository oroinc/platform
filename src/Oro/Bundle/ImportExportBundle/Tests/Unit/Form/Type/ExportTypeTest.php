<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ExportTypeTest extends FormIntegrationTestCase
{
    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var ExportType */
    private $exportType;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->exportType = new ExportType($this->processorRegistry);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->exportType], [])
        ];
    }

    /**
     * @dataProvider processorAliasesDataProvider
     */
    public function testSubmit(
        array $processorAliasesFromRegistry,
        array|string $processorAliasesPassedToForm,
        array $expectedChoices,
        ?string $usedAlias
    ) {
        $entityName = 'TestEntity';

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT, $entityName)
            ->willReturn($processorAliasesFromRegistry);

        $form = $this->factory->create(ExportType::class, null, [
            'entityName' => $entityName,
            'processorAlias' => $processorAliasesPassedToForm
        ]);

        $processorAliasConfig = $form->get('processorAlias')->getConfig();
        $this->assertEquals('oro.importexport.export.popup.options.label', $processorAliasConfig->getOption('label'));
        $this->assertEquals(
            $expectedChoices,
            $form->get('processorAlias')->createView()->vars['choices']
        );
        $this->assertTrue($processorAliasConfig->getOption('required'));
        $this->assertNull($processorAliasConfig->getOption('placeholder'));

        $form->submit(['processorAlias' => $usedAlias]);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var ExportData $data */
        $data = $form->getData();
        $this->assertInstanceOf(ExportData::class, $data);
        $this->assertEquals($usedAlias, $data->getProcessorAlias());
    }

    public function processorAliasesDataProvider(): array
    {
        return [
            [
                ['first_alias', 'second_alias'],
                ['first_alias', 'second_alias'],
                [
                    new ChoiceView('first_alias', 'first_alias', 'oro.importexport.export.first_alias'),
                    new ChoiceView('second_alias', 'second_alias', 'oro.importexport.export.second_alias')
                ],
                'second_alias'
            ],
            [
                ['first_alias', 'second_alias', 'third_alias'],
                ['first_alias'],
                [
                    new ChoiceView('first_alias', 'first_alias', 'oro.importexport.export.first_alias')
                ],
                'first_alias'
            ],
            [
                ['first_alias', 'second_alias', 'third_alias'],
                'first_alias',
                [
                    new ChoiceView('first_alias', 'first_alias', 'oro.importexport.export.first_alias')
                ],
                'first_alias'
            ],
            [
                [],
                ['first_alias'],
                [],
                null
            ],
        ];
    }
}
