<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;

class ImportProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImportProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var array
     */
    protected $item = array('test' => 'test');

    /**
     * @var object
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new \stdClass();

        $this->context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->setMethods(array('getOption', 'addFailureException'))
            ->getMockForAbstractClass();

        $this->serializer = $this->getMockBuilder('Symfony\Component\Serializer\SerializerInterface')
            ->getMockForAbstractClass();

        $this->processor = new ImportProcessor();
        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
    }

    protected function setProcessExpects()
    {
        $this->context->expects($this->once())
            ->method('getOption', 'addFailureException')
            ->with('entityName')
            ->will($this->returnValue('\stdClass'));
        $this->context->expects($this->once())
            ->method('setValue')
            ->with('itemData', $this->item);
        $this->context->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue([]));

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($this->item, '\stdClass', null)
            ->will($this->returnValue($this->object));
    }

    public function testProcessMinimum()
    {
        $this->setProcessExpects();

        $this->assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testProcess()
    {
        $this->setProcessExpects();

        $this->context->expects($this->never())
            ->method('addFailureException');

        $converter = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface')
            ->setMethods(array('convertToImportFormat'))
            ->getMockForAbstractClass();
        $converter->expects($this->once())
            ->method('convertToImportFormat')
            ->with($this->item)
            ->will($this->returnArgument(0));

        $strategy = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface')
            ->setMethods(array('process'))
            ->getMockForAbstractClass();
        $strategy->expects($this->once())
            ->method('process')
            ->with($this->object)
            ->will($this->returnArgument(0));

        $this->processor->setDataConverter($converter);
        $this->processor->setStrategy($strategy);
        $this->assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testSetEntityName()
    {
        $entityName = 'TestEntity';

        $dataConverter
            = $this->getMock('Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\EntityNameAwareDataConverter');
        $dataConverter->expects($this->once())->method('setEntityName')->with($entityName);

        $strategy = $this->getMock('Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\EntityNameAwareStrategy');
        $strategy->expects($this->once())->method('setEntityName')->with($entityName);

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setStrategy($strategy);
        $this->processor->setEntityName($entityName);
        $this->assertAttributeEquals($entityName, 'entityName', $this->processor);
    }
}
