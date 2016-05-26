<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Provider\ContextGridProvider;

class ContextGridProviderTest extends \PHPUnit_Framework_TestCase
{
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityFacade;

    /**
     * @var ContextGridProvider
     */
    protected $provider;

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
            ],
            [
                'name' => 'Oro\Bundle\UserBundle\Entity\Contact',
                'label' => 'label2',
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
        
        $this->mockSecurityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->entityProvider->expects($this->any())
            ->method('getEntities')
            ->withAnyParameters()
            ->will($this->returnValue($entities));

        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'has', 'get'])
            ->getMock();

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with($this->routingHelper->getUrlSafeClassName($this->entityClass))
            ->will($this->returnValue($this->configProvider));

        $this->configProvider->expects($this->any())
            ->method('has')
            ->with('context')
            ->willReturn(true);

        $this->configProvider->expects($this->any())
            ->method('get')
            ->with('context')
            ->will($this->returnValue($this->expectedGridName));

        $this->provider = new ContextGridProvider(
            $this->routingHelper,
            $this->entityProvider,
            $this->configProvider,
            $this->mockSecurityFacade
        );
    }

    /**
     * @param array     $permissions,
     * @param array     $supportActivityTarget
     * @param array     $expectedArray
     * @param integer   $expectedCount
     *
     * @dataProvider getSupportedTargetsDataProvider
     */
    public function testGetSupportedTargets($permissions, $supportActivityTarget, $expectedArray, $expectedCount)
    {

        $entities = [
            [
                'name' => 'Oro\Bundle\UserBundle\Entity\User',
                'label' => 'label1',
            ],
            [
                'name' => 'Oro\Bundle\UserBundle\Entity\Contact',
                'label' => 'label2',
            ]
        ];

        $routingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
                            ->disableOriginalConstructor()
                            ->setMethods(['getUrlSafeClassName', 'resolveEntityClass'])
                            ->getMock();

        $routingHelper->expects($this->any())
                      ->method('getUrlSafeClassName')
                      ->will($this->returnValue(true));

        $mockEntity = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(['supportActivityTarget'])
            ->getMock();

        $mockEntity->expects($this->any())
            ->method('supportActivityTarget')
            ->will($this->returnValueMap($supportActivityTarget));

        $entityProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getEntities'])
            ->getMock();

        $entityProvider->expects($this->any())
            ->method('getEntities')
            ->withAnyParameters()
            ->will($this->returnValue($entities));

        $mockSecurityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();

        $mockSecurityFacade->expects($this->atLeastOnce())
            ->method('isGranted')
            ->will($this->returnValueMap($permissions));

        $configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'has', 'get'])
            ->getMock();

        $configProvider->expects($this->any())
            ->method('getConfig')
            ->with($this->routingHelper->getUrlSafeClassName($this->entityClass))
            ->will($this->returnValue($configProvider));

        $configProvider->expects($this->any())
            ->method('has')
            ->with('context')
            ->willReturn(true);

        $configProvider->expects($this->any())
            ->method('get')
            ->with('context')
            ->will($this->returnValue(true));

        $this->provider = new ContextGridProvider(
            $routingHelper,
            $entityProvider,
            $configProvider,
            $mockSecurityFacade
        );

        $targets = $this->provider->getSupportedTargets($mockEntity);

        $this->assertCount($expectedCount, $targets);
        $this->assertEquals($expectedArray, $targets);
    }

    public function testGetContextGridByEntity()
    {
        $gridName = $this->provider->getContextGridByEntity($this->entityClass);
        $this->assertEquals($this->expectedGridName, $gridName);
    }

    /**
     * @return array
     */
    public function getSupportedTargetsDataProvider()
    {
        return [
            [
                'permissions' => [
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\User', true],
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\Contact', true]
                ],
                'supportActivityTarget' => [
                    ['Oro\Bundle\UserBundle\Entity\User', true],
                    ['Oro\Bundle\UserBundle\Entity\Contact', true]
                ],
                'expectedArray' => [
                    [
                        'label' => 'label1',
                        'className' => true,
                        'first' => true,
                        'gridName' => true
                    ],
                    [
                        'label' => 'label2',
                        'className' => true,
                        'first' => false,
                        'gridName' => true
                    ]
                ],
                'expectedCount' => 2
            ],
            [
                'permissions' => [
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\User', false],
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\Contact', true]
                ],
                'supportActivityTarget' => [
                    ['Oro\Bundle\UserBundle\Entity\Contact', true]
                ],
                'expectedArray' => [
                    [
                        'label' => 'label2',
                        'className' => true,
                        'first' => true,
                        'gridName' => true
                    ]
                ],
                'expectedCount' => 1
            ],
            [
                'permissions' => [
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\User', true],
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\Contact', false]
                ],
                'supportActivityTarget' => [
                    ['Oro\Bundle\UserBundle\Entity\User', true],
                ],
                'expectedArray' => [
                    [
                        'label' => 'label1',
                        'className' => true,
                        'first' => true,
                        'gridName' => true
                    ]
                ],
                'expectedCount' => 1
            ],
            [
                'permissions' => [
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\User', false],
                    ['VIEW', 'entity:Oro\Bundle\UserBundle\Entity\Contact', false]
                ],
                'supportActivityTarget' => [],
                'expectedArray' => [],
                'expectedCount' => 0
            ],
        ];
    }
}
