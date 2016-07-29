<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;
use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;

class ExportTypeTest extends FormIntegrationTestCase
{
    const ENTITY_NAME = 'testName';

    /**
     * @var ProcessorRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processorRegistry;

    /**
     * @var ExportType
     */
    protected $exportType;

    protected function setUp()
    {
        parent::setUp();

        $this->processorRegistry = $this->getMockBuilder(ProcessorRegistry::class)->getMock();
        $this->exportType = new ExportType($this->processorRegistry);
    }

    public function testSubmit()
    {
        $entityName = 'TestEntity';
        $processorAliases = [
            'first_alias',
            'second_alias'
        ];
        $expectedChoices = [
            'first_alias' => 'oro.importexport.export.first_alias',
            'second_alias' => 'oro.importexport.export.second_alias',
        ];
        $usedAlias = 'second_alias';

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT, $entityName)
            ->willReturn($processorAliases);

        $form = $this->factory->create($this->exportType, null, ['entityName' => $entityName]);

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
