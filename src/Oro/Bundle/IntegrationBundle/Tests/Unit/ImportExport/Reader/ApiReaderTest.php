<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Reader\ApiReader;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

class ApiReaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApiReader */
    protected $reader;

    /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var ContextRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextRegistry;

    /** @var ConnectorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $connector;

    /** @var \Closure|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    public function setUp()
    {
        $this->contextRegistry = $this->getMock(
            'Oro\Bundle\ImportExportBundle\Context\ContextRegistry',
            ['setStepExecution']
        );

        $this->reader = $this->getMock(
            'Oro\Bundle\IntegrationBundle\ImportExport\Reader\ApiReader',
            ['getContext'],
            [$this->contextRegistry]
        );

        $this->reader->

        $this->logger = '';
        $this->connector = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface');

        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->context->expects($this->at(0))
            ->method('getOption')
            ->with('logger')
            ->will($this->returnValue($this->logger));
        $this->context->expects($this->at(1))
            ->method('getOption')
            ->with('connector')
            ->will($this->returnValue($this->connector));

        $this->reader->setStepExecution(
            $this->getMock('Oro\Bundle\BatchBundle\Entity\StepExecution', [], [], '', false)
        );
    }

    public function tearDown()
    {
        unset($this->reader, $this->connector, $this->context, $this->logger);
    }

    /**
     * Test reading
     *
     * @dataProvider  getTestData
     * @param boolean $isDataNull
     */
    public function testRead($isDataNull)
    {
        $this->reader->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $this->reader->read();
    }

    /**
     * @return array
     */
    public function getTestData()
    {
        return [
            ['good' => true],
            ['bad' => false],
        ];
    }
}
