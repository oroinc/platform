<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;

class PersistentBatchWriterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $contextRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var PersistentBatchWriter */
    protected $writer;

    protected function setUp()
    {
        $this->registry        = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->contextRegistry = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $this->entityManager   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($this->entityManager));
    }

    /**
     * @param array $configuration
     *
     * @dataProvider configurationProvider
     */
    public function testWrite(array $configuration)
    {
        $writer = $this->getWriter();

        $this->entityManager->expects($this->at(1))
            ->method('beginTransaction');

        $fooItem = $this->getMock('FooItem');
        $barItem = $this->getMock('BarItem');

        $this->entityManager->expects($this->at(2))
            ->method('persist')
            ->with($fooItem);

        $this->entityManager->expects($this->at(3))
            ->method('persist')
            ->with($barItem);

        $this->entityManager->expects($this->at(4))
            ->method('flush');

        $this->entityManager->expects($this->at(5))
            ->method('commit');

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $writer->setStepExecution($stepExecution);
        $writer->write([$fooItem, $barItem]);
    }

    /**
     * @return array
     */
    public function configurationProvider()
    {
        return [
            'no clear flag'    => [[]],
            'clear flag false' => [[EntityWriter::SKIP_CLEAR => false]],
            'clear flag true'  => [[EntityWriter::SKIP_CLEAR => true]],
        ];
    }

    /**
     * @param bool $isManagerOpen
     *
     * @return PersistentBatchWriter
     */
    protected function getWriter($isManagerOpen = true)
    {
        $this->entityManager->expects($this->at(0))
            ->method('isOpen')
            ->will($this->returnValue($isManagerOpen));

        return new PersistentBatchWriter($this->registry, $this->eventDispatcher, $this->contextRegistry);
    }
}
