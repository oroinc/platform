<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;
use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;

class ExportTemplateTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProcessorRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processorRegistry;

    /**
     * @var ExportTemplateType
     */
    protected $exportTemplateType;

    protected function setUp()
    {
        parent::setUp();

        $this->processorRegistry = $this->getMockBuilder(ProcessorRegistry::class)->getMock();
        $this->exportTemplateType = new ExportTemplateType($this->processorRegistry);
    }

    public function testSubmit()
    {
        $entityName = 'TestEntity';
        $processorAliases = [
            'first_alias',
            'second_alias'
        ];
        $expectedChoices = [
            'first_alias' => 'oro.importexport.export_template.first_alias',
            'second_alias' => 'oro.importexport.export_template.second_alias',
        ];
        $usedAlias = 'second_alias';

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT_TEMPLATE, $entityName)
            ->willReturn($processorAliases);

        $form = $this->factory->create($this->exportTemplateType, null, ['entityName' => $entityName]);

        $processorAliasConfig = $form->get('processorAlias')->getConfig();
        $this->assertEquals('oro.importexport.export.processor', $processorAliasConfig->getOption('label'));
        $this->assertEquals($expectedChoices, $processorAliasConfig->getOption('choices'));
        $this->assertTrue($processorAliasConfig->getOption('required'));
        $this->assertNull($processorAliasConfig->getOption('placeholder'));

        $form->submit(['processorAlias' => $usedAlias]);
        $this->assertTrue($form->isValid());

        /** @var ExportData $data */
        $data = $form->getData();
        $this->assertInstanceOf(ExportData::class, $data);
        $this->assertEquals($usedAlias, $data->getProcessorAlias());
    }
}
