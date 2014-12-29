<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\ImportExport;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\TrackingBundle\ImportExport\ContextReader;

class ContextReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stepExecution;

    /**
     * @var ContextReader
     */
    protected $reader;

    public function setUp()
    {
        $this->contextRegistry = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this
            ->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->stepExecution = $this
            ->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->reader = new ContextReader($this->contextRegistry);
    }

    public function testRead()
    {
        $data = [
            'name'  => 'event_name',
            'value' => 'done'
        ];

        $this->context
            ->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue($data));

        $this->context
            ->expects($this->once())
            ->method('hasOption')
            ->with($this->equalTo('data'))
            ->will($this->returnValue(true));

        $this->contextRegistry
            ->expects($this->once())
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->reader->setStepExecution($this->stepExecution);
        $result = $this->reader->read();
        $this->assertEquals($data, $result);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration reader must contain "data".
     */
    public function testReadFailed()
    {
        $this->context
            ->expects($this->once())
            ->method('hasOption')
            ->with($this->equalTo('data'))
            ->will($this->returnValue(false));

        $this->contextRegistry
            ->expects($this->once())
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->reader->setStepExecution($this->stepExecution);
        $this->reader->read();
    }
}
