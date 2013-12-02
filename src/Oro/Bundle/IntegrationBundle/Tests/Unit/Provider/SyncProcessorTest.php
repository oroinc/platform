<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;

class SyncProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SyncProcessor */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $jobExecutor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /**
     * Setup test obj and mock
     */
    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('createQueryBuilder'))
            ->getMock();

        $this->processorRegistry = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');

        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\JobExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');

        $this->processor = new SyncProcessor(
            $this->em,
            $this->processorRegistry,
            $this->jobExecutor,
            $this->registry
        );
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        unset($this->em, $this->processorRegistry, $this->registry, $this->jobExecutor, $this->processor);
    }

    /**
     * Test process method
     */
    public function testProcess()
    {
        $this->markTestSkipped('To be finished');
        $channelName = 'testChannel';
        $force = false;

        $this->processor->process($channelName, $force);
    }
}
