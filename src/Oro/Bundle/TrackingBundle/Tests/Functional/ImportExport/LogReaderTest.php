<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\ImportExport;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\TrackingBundle\ImportExport\LogReader;

class LogReaderTest extends \PHPUnit_Framework_TestCase
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
     * @var LogReader
     */
    protected $reader;

    public function setUp()
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tracking';

        $this->fs = new Filesystem();

        $this->fs->mkdir($this->directory);

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

        $this->reader = new LogReader($this->contextRegistry);
    }

    public function testRead()
    {
        $data = [
            'name'  => 'event_name',
            'value' => 'done'
        ];

        $filename = $this->directory . DIRECTORY_SEPARATOR . 'valid.log';
        $this->fs->dumpFile($filename, json_encode($data));

        $this->context
            ->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue($filename));

        $this->context
            ->expects($this->once())
            ->method('hasOption')
            ->with($this->equalTo('file'))
            ->will($this->returnValue(true));

        $this->contextRegistry
            ->expects($this->exactly(3))
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->reader->setStepExecution($this->stepExecution);
        $result = $this->reader->read();
        $this->assertEquals($data, $result);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration reader must contain "file".
     */
    public function testReadFailed()
    {
        $this->context
            ->expects($this->once())
            ->method('hasOption')
            ->with($this->equalTo('file'))
            ->will($this->returnValue(false));

        $this->contextRegistry
            ->expects($this->once())
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->reader->setStepExecution($this->stepExecution);
        $this->reader->read();
    }

    public function testReadFileNotValid()
    {
        $filename = $this->directory . DIRECTORY_SEPARATOR . 'not_valid.log';
        $this->fs->touch($filename);

        $this->context
            ->expects($this->once())
            ->method('hasOption')
            ->with($this->equalTo('file'))
            ->will($this->returnValue(true));

        $this->context
            ->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue($filename));

        $this->contextRegistry
            ->expects($this->exactly(3))
            ->method('getByStepExecution')
            ->will($this->returnValue($this->context));

        $this->reader->setStepExecution($this->stepExecution);
        $this->assertNull($this->reader->read());
        $this->assertNull($this->reader->read());
    }
}
