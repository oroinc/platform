<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;

class ActionsConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmpty()
    {
        $config = new ActionsConfig();
        $this->assertTrue($config->isEmpty());
    }

    public function testGetAddRemove()
    {
        $actionConfig = new ActionConfig();
        $config = new ActionsConfig();
        $this->assertEmpty($config->getActions());

        $config->addAction('action1', $actionConfig);
        $this->assertNotEmpty($config->getActions());
        $this->assertCount(1, $config->getActions());

        $this->assertSame($actionConfig, $config->getAction('action1'));
        $this->assertNull($config->getAction('action2'));

        $config->addAction('action2');
        $this->assertEquals(new ActionConfig(), $config->getAction('action2'));
        $this->assertCount(2, $config->getActions());

        $config->removeAction('action1');
        $config->removeAction('action2');
        $this->assertTrue($config->isEmpty());
    }

    public function testToArrayAndClone()
    {
        $config = new ActionsConfig();
        $actionConfig = new ActionConfig();
        $actionConfig->setAclResource('access_entity_view');

        $config->addAction('action1', $actionConfig);
        $config->addAction('action2', $actionConfig);

        $this->assertSame(
            [
                'action1' => ['acl_resource' => 'access_entity_view'],
                'action2' => ['acl_resource' => 'access_entity_view']
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        $this->assertEquals($config, $cloneConfig);
        $this->assertNotSame(
            $config->getAction('action1'),
            $cloneConfig->getAction('action1')
        );
    }
}
