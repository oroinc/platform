<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;

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

    public function testBuildFormShouldAddEventListener()
    {
        $builder = $this->getBuilderMock();
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $builder->expects($this->once())
            ->method('addEventListener');

        $this->exportType->buildForm($builder, ['entityName' => self::ENTITY_NAME]);
    }

    /**
     * @dataProvider getProcessorData
     * @param string $processor1
     * @param string $processor2
     */
    public function testBuildFormShouldCreateCorrectChoices($processor1, $processor2)
    {
        $builder = $this->getBuilderMock();
        $phpunitTestCase = $this;
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([$processor1, $processor2]);

        $builder->expects($this->once())
            ->method('add')
            ->will($this->returnCallback(
                function ($name, $type, $options) use ($phpunitTestCase, $processor1, $processor2) {
                    $choices = $options['choices'];
                    $phpunitTestCase->assertArrayHasKey(
                        $processor1,
                        $choices
                    );
                    $phpunitTestCase->assertArrayHasKey(
                        $processor2,
                        $choices
                    );
                }
            ));

        $this->exportType->buildForm($builder, ['entityName' => self::ENTITY_NAME]);
    }

    public function getProcessorData()
    {
        return [
            ['process1', 'process2']
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder(FormBuilderInterface::class)->getMock();
    }
}
