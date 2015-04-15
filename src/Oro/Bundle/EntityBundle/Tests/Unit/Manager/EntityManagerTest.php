<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\Manager\EntityManager;

class EntityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContainer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $routingHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEntity;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $entityClass = "Oro\\Bundle\\UserBundle\\Entity\\User";

    /**
     * @var string
     */
    protected $expectedGridName = 'mygrig1';

    protected function setUp()
    {
        $entities = [
            [
                'name' => $this->entityClass,
                'label' => 'label1',
            ]
        ];

        $this->routingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEntity = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(['supportActivityTarget'])
            ->getMock();

        $this->mockEntity->expects($this->any())
            ->method('supportActivityTarget')
            ->with($this->entityClass)
            ->will($this->returnValue(true));

        $this->entityProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getEntities'])
            ->getMock();

        $this->entityProvider->expects($this->any())
            ->method('getEntities')
            ->withAnyParameters()
            ->will($this->returnValue($entities));

        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'get'])
            ->getMock();

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with($this->routingHelper->encodeClassName($this->entityClass))
            ->will($this->returnValue($this->configProvider));

        $this->configProvider->expects($this->any())
            ->method('get')
            ->with('context-grid')
            ->will($this->returnValue($this->expectedGridName));

        $this->mockContainer = $this
            ->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->manager = new EntityManager($this->mockContainer, $this->routingHelper);
    }

    public function testGetSupportedTargets()
    {
        $targets = $this->manager->getSupportedTargets($this->entityProvider, $this->configProvider, $this->mockEntity);

        $this->assertCount(1, $targets);
    }

    public function testGetContextGridByEntity()
    {
        $gridName = $this->manager->getContextGridByEntity(
            $this->configProvider,
            $this->entityClass
        );

        $this->assertEquals($this->expectedGridName, $gridName);
    }
}
