<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EmailBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;

class ConfigureSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CACHE_KEY = 'testCache.Key';
    const TEST_CLASS_NAME = 'someClassName';

    /** @var ConfigSubscriber */
    protected $subscriber;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\Cache')
            ->disableOriginalConstructor()->getMock();

        $this->subscriber = new ConfigSubscriber($this->cache, self::TEST_CACHE_KEY);
    }

    protected function tearDown()
    {
        unset($this->cache);
        unset($this->subscriber);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            array(Events::PRE_PERSIST_CONFIG => 'persistConfig'),
            ConfigSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider changeSetProvider
     *
     * @param $scope
     * @param $change
     * @param $shouldClearCache
     */
    public function testPersistConfig($scope, $change, $shouldClearCache)
    {
        $config = new Config(new EntityConfigId($scope, 'Test\Entity'));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly($scope === 'email' ? 1 : 0))
            ->method('getConfigChangeSet')
            ->with($this->identicalTo($config))
            ->will($this->returnValue($change));

        $this->cache->expects($this->exactly($shouldClearCache ? 1 : 0))
            ->method('delete')
            ->with(self::TEST_CACHE_KEY);

        $this->subscriber->persistConfig(new PersistConfigEvent($config, $configManager));
    }

    /**
     * @return array
     */
    public function changeSetProvider()
    {
        return array(
            'email config changed'     => array(
                'scope'            => 'email',
                'change'           => array('available_in_template' => array(true, false)),
                'shouldClearCache' => true
            ),
            'email config not changed' => array(
                'scope'            => 'email',
                'change'           => array(),
                'shouldClearCache' => false
            ),
            'not email config'         => array(
                'scope'            => 'someConfigScope',
                'change'           => array(),
                'shouldClearCache' => false
            )
        );
    }
}
