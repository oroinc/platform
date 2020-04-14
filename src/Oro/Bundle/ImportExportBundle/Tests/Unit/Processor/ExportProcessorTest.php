<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;

class ExportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExportProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->setMethods(array('getOption'))
            ->getMockForAbstractClass();
        $this->context->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue([]));

        $this->processor = new ExportProcessor();
    }

    public function testProcess()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Serializer must be injected.');

        $entity = $this->createMock(\stdClass::class);

        $this->processor->setImportExportContext($this->context);
        $this->processor->process($entity);
    }

    public function testProcessWithDataConverter()
    {
        $entity = $this->createMock(\stdClass::class);
        $serializedValue = array('serialized');
        $expectedValue = array('expected');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('normalize')
            ->with($entity, null)
            ->willReturn($serializedValue);
        $serializer->expects($this->once())
            ->method('encode')
            ->with($serializedValue, null)
            ->willReturnArgument(0);

        $dataConverter = $this->createMock('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface');
        $dataConverter->expects($this->once())
            ->method('convertToExportFormat')
            ->with($serializedValue)
            ->will($this->returnValue($expectedValue));

        $this->processor->setSerializer($serializer);
        $this->processor->setDataConverter($dataConverter);
        $this->processor->setImportExportContext($this->context);

        $this->assertEquals($expectedValue, $this->processor->process($entity));
    }

    public function testProcessWithoutDataConverter()
    {
        $entity = $this->createMock(\stdClass::class);
        $expectedValue = array('expected');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('normalize')
            ->with($entity, null)
            ->willReturn($expectedValue);
        $serializer->expects($this->once())
            ->method('encode')
            ->with($expectedValue, null)
            ->willReturnArgument(0);

        $this->processor->setSerializer($serializer);
        $this->processor->setImportExportContext($this->context);

        $this->assertEquals($expectedValue, $this->processor->process($entity));
    }

    public function testSetImportExportContextWithoutQueryBuilder()
    {
        $this->context->expects($this->once())->method('getOption')
            ->will($this->returnValue(null));

        $dataConverter = $this->createMock('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface');
        $dataConverter->expects($this->never())->method($this->anything());

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setImportExportContext($this->context);
    }

    public function testSetImportExportContextWithQueryBuilderIgnored()
    {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())->method('getOption')
            ->will($this->returnValue($queryBuilder));

        $dataConverter = $this->createMock('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface');
        $dataConverter->expects($this->never())->method($this->anything());

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setImportExportContext($this->context);
    }

    public function testSetImportExportContextWithQueryBuilder()
    {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())->method('getOption')
            ->will($this->returnValue($queryBuilder));

        $dataConverter = $this->createMock(
            'Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\QueryBuilderAwareDataConverter'
        );
        $dataConverter->expects($this->once())
            ->method('setQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setImportExportContext($this->context);
    }

    public function testSetImportExportContextFailsWithInvalidQueryBuilder()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration of processor contains invalid "queryBuilder" option.'
            . ' "Doctrine\ORM\QueryBuilder" type is expected, but "stdClass" is given'
        );

        $this->context->expects($this->once())->method('getOption')
            ->will($this->returnValue(new \stdClass()));

        $dataConverter = $this->createMock(
            'Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\QueryBuilderAwareDataConverter'
        );
        $dataConverter->expects($this->never())->method($this->anything());

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setImportExportContext($this->context);
    }

    public function testSetEntityName()
    {
        $entityName = 'TestEntity';

        $dataConverter
            = $this->createMock('Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\EntityNameAwareDataConverter');
        $dataConverter->expects($this->once())->method('setEntityName')->with($entityName);

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setEntityName($entityName);
        $this->assertAttributeEquals($entityName, 'entityName', $this->processor);
    }
}
