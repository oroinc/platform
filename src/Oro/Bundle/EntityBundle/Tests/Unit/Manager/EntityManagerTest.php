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
    protected $mockEntity;

    /**
     * @var EntityManager
     */
    protected $manager;

    protected $entityAlias = "context-item-66d4d60cf6b25ebb7373af805846c334";

    protected function setUp()
    {
        $entities = [
            $this->entityAlias => [
                'name' => 'abc1',
                'label' => 'label1',
            ]
        ];

        $this->mockEntity = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(['supportActivityTarget'])
            ->getMock();

        $this->mockEntity->expects($this->any())
            ->method('supportActivityTarget')
            ->with('abc1')
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

        $this->mockContainer = $this
            ->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->manager = new EntityManager($this->mockContainer);
    }

    public function testGetSupportedTargets()
    {
        $targets = $this->manager->getSupportedTargets($this->entityProvider, $this->mockEntity);

        $this->assertCount(1, $targets);
    }

    public function testGetContextGridByEntity()
    {
        $expectedGridName = 'mygrig1';

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with('abc1')
            ->will($this->returnValue($this->configProvider));

        $this->configProvider->expects($this->any())
            ->method('get')
            ->with('context-grid')
            ->will($this->returnValue($expectedGridName));

        $gridName = $this->manager->getContextGridByEntity(
            $this->entityProvider,
            $this->configProvider,
            $this->mockEntity,
            $this->entityAlias
        );

        $this->assertEquals($expectedGridName, $gridName);
    }
}
