<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ActivityListChainProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListChainProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $routeHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TestActivityProvider */
    protected $testActivityProvider;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->routeHelper    = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator     = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $this->testActivityProvider = new TestActivityProvider();

        $this->provider = new ActivityListChainProvider(
            $this->doctrineHelper,
            $this->configManager,
            $this->translator,
            $this->routeHelper
        );
        $this->provider->addProvider($this->testActivityProvider);
    }

    public function testGetSupportedActivities()
    {
        $this->assertEquals(
            [TestActivityProvider::ACTIVITY_CLASS_NAME],
            $this->provider->getSupportedActivities()
        );
    }

    public function testIsSupportedEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue(TestActivityProvider::ACTIVITY_CLASS_NAME));
        $this->assertTrue($this->provider->isSupportedEntity($testEntity));
    }

    public function testIsSupportedEntityWrongEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->will($this->returnValue('\stdClass'));
        $this->assertFalse($this->provider->isSupportedEntity($testEntity));
    }

    public function testGetSubject()
    {
        $testEntity          = new \stdClass();
        $testEntity->subject = 'test';
        $this->assertEquals('test', $this->provider->getSubject($testEntity));
    }

    public function testGetEmptySubject()
    {
        $testEntity = new TestTarget();
        $this->assertNull($this->provider->getSubject($testEntity));
    }

    public function testGetTargetEntityClasses()
    {
        $correctTarget    = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\CorrectEntity');
        $notCorrectTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\NotCorrectEntity');
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        $correctTarget,
                        $notCorrectTarget
                    ]
                )
            );

        $this->assertEquals(['Acme\\DemoBundle\\Entity\\CorrectEntity'], $this->provider->getTargetEntityClasses());
    }

    public function testGetProviderForEntity()
    {
        $testEntity = new \stdClass();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($testEntity)
            ->willReturn('Test\Entity');
        $this->assertEquals($this->testActivityProvider, $this->provider->getProviderForEntity($testEntity));
    }

    public function testGetActivityListOption()
    {
        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $configId     = new EntityConfigId('entity', 'Test\Entity');
        $entityConfig = new Config($configId);

        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');
        $entityConfigProvider->expects($this->once())->method('getConfig')->willReturn($entityConfig);
        $this->translator->expects($this->once())->method('trans')->with('test_label')->willReturn('test_label');
        $this->routeHelper->expects($this->once())->method('encodeClassName')
            ->willReturn('Test_Entity');
        $this->configManager->expects($this->once())->method('getProvider')->willReturn($entityConfigProvider);

        $result = $this->provider->getActivityListOption();
        $this->assertEquals(
            [
                'Test_Entity' => [
                    'icon'     => 'test_icon',
                    'label'    => 'test_label',
                    'template' => 'test_template.js.twig',
                    'routes'   => [
                        'delete' => 'test_delete_route'
                    ],
                    'has_comments' => true,
                ]
            ],
            $result
        );
    }

    public function testGetUpdatedActivityList()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();

        $activityEntity = new ActivityList();
        $repo->expects($this->once())->method('findOneBy')->willReturn($activityEntity);
        $em->expects($this->once())->method('getRepository')->willReturn($repo);

        $testEntity = new \stdClass();
        $testEntity->subject = 'testSubject';

        $this->testActivityProvider->setTargets([new \stdClass()]);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($entity) use ($testEntity) {
                    if ($entity === $testEntity) {
                        return 'Test\Entity';
                    }

                    return get_class($entity);
                }
            );

        $result = $this->provider->getUpdatedActivityList($testEntity, $em);
        $this->assertEquals('update', $result->getVerb());
        $this->assertEquals('testSubject', $result->getSubject());
    }
}
