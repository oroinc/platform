<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestIntegrationType;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestConnector;

class SyncSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var SyncScheduler */
    protected $scheduler;

    public function setUp()
    {
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->typesRegistry = new TypesRegistry();
        $this->scheduler     = new SyncScheduler($this->registry, $this->typesRegistry);
    }

    public function tearDown()
    {
        unset($this->em, $this->registry, $this->typesRegistry, $this->scheduler);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Connectors not found for integration "testType"
     */
    public function testScheduleRegistryError()
    {
        $integration = new Integration();
        $integration->setType('testType');
        $integration->setEnabled(true);

        $this->scheduler->schedule($integration, '');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to schedule job for "testConnectorType" connector type
     */
    public function testScheduleConnectorError()
    {
        $testIntegrationType = 'testIntegrationType';
        $testConnectorType   = 'testConnectorType';

        $integration = new Integration();
        $integration->setType($testIntegrationType);
        $this->typesRegistry->addChannelType($testIntegrationType, new TestIntegrationType());
        $this->typesRegistry->addConnectorType($testConnectorType, $testIntegrationType, new TestConnector());
        $integration->setEnabled(true);

        $this->scheduler->schedule($integration, $testConnectorType);
    }
}
