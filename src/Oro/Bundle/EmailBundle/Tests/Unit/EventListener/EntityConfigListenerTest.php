<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EntityConfigListener;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

class EntityConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CACHE_KEY = 'testCache.Key';
    const TEST_CLASS_NAME = 'someClassName';

    /** @var EntityConfigListener */
    protected $listener;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\Cache')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new EntityConfigListener($this->cache, self::TEST_CACHE_KEY);
    }

    protected function tearDown()
    {
        unset($this->cache);
        unset($this->listener);
    }

    /**
     * @dataProvider changeSetProvider
     *
     * @param $scope
     * @param $changeSet
     * @param $shouldClearCache
     */
    public function testPreFlush($scope, $changeSet, $shouldClearCache)
    {
        $config = new Config(new FieldConfigId($scope, 'Test\Entity', 'testField'));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly($scope === 'email' ? 1 : 0))
            ->method('getConfigChangeSet')
            ->with($this->identicalTo($config))
            ->will($this->returnValue($changeSet));

        $this->cache->expects($this->exactly($shouldClearCache ? 1 : 0))
            ->method('delete')
            ->with(self::TEST_CACHE_KEY);

        $this->listener->preFlush(new PreFlushConfigEvent([$scope => $config], $configManager));
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
