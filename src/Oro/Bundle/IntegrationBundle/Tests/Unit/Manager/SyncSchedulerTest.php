<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestIntegrationType;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector;

class SyncSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var SyncScheduler */
    protected $scheduler;

    public function setUp()
    {
        $this->em            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->typesRegistry = new TypesRegistry();
        $this->scheduler     = new SyncScheduler($this->em, $this->typesRegistry);
    }

    public function tearDown()
    {
        unset($this->em, $this->typesRegistry, $this->scheduler);
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

    public function testSchedule()
    {
        $testIntegrationType = 'testIntegrationType';
        $testConnectorType   = 'testConnectorType';
        $testId              = 22;

        $integration = new Integration();
        $integration->setType($testIntegrationType);
        $integration->setEnabled(true);
        $ref = new \ReflectionProperty(get_class($integration), 'id');

        $ref->setAccessible(true);
        $ref->setValue($integration, $testId);
        $this->typesRegistry->addChannelType($testIntegrationType, new TestIntegrationType());
        $this->typesRegistry->addConnectorType($testConnectorType, $testIntegrationType, new TestTwoWayConnector());

        $that = $this;

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $this->em->expects($this->once())->method('getUnitOfWork')->will($this->returnValue($uow));
        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();
        $metadataFactory->expects($this->once())->method('getMetadataFor')
            ->will($this->returnValue(new ClassMetadata('testEntity')));
        $this->em->expects($this->once())->method('getMetadataFactory')->will($this->returnValue($metadataFactory));
        $uow->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
            ->will(
                $this->returnCallback(
                    function (Job $job) use ($that, $testId, $testConnectorType) {
                        $expectedArgs = [
                            '--integration=' . $testId,
                            sprintf('--connector=testConnectorType', $testConnectorType),
                            '--params=a:0:{}',
                        ];

                        $that->assertEquals($expectedArgs, $job->getArgs());
                    }
                )
            );
        $uow->expects($this->once())->method('computeChangeSet');

        $this->scheduler->schedule($integration, $testConnectorType, [], false);
    }
}
