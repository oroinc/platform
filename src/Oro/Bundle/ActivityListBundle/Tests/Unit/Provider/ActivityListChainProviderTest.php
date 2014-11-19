<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;
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
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->routeHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
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
        $testEntity = new \stdClass();
        $testEntity->subject = 'test';
        $this->assertEquals('test', $this->provider->getSubject($testEntity));
    }

    public function testGetTargetEntityClasses()
    {
        $correctTarget = new EntityConfigId('entity', 'Acme\\DemoBundle\\Entity\\CorrectEntity');
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

    /*public function testGetActivityListEntitiesByActivityEntity()
    {
        $testActivity = new \stdClass();
        $testActivity->subject = 'test subject';
        $testActivity->id = 150;

        $this->testActivityProvider->setTargets([]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue(get_class($testActivity)));

        $resultActivityList = $this->provider->getActivityListEntitiesByActivityEntity($testActivity);

        $a = 1;
    }*/
}
