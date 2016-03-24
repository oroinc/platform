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

        $config->addAction('ACTION1', $actionConfig);
        $this->assertNotEmpty($config->getActions());
        $this->assertCount(1, $config->getActions());

        $this->assertSame($actionConfig, $config->getAction('ACTION1'));
        $this->assertNull($config->getAction('ACTION2'));

        $config->addAction('ACTION2');
        $this->assertEquals(new ActionConfig(), $config->getAction('ACTION2'));
        $this->assertCount(2, $config->getActions());

        $config->removeAction('ACTION1');
        $config->removeAction('ACTION2');
        $this->assertTrue($config->isEmpty());
    }

    public function testToArrayAndClone()
    {
        $config = new ActionsConfig();
        $actionConfig = new ActionConfig();
        $actionConfig->setAclResource('access_entity_view');

        $config->addAction('ACTION1', $actionConfig);
        $config->addAction('ACTION2', $actionConfig);

        $this->assertSame(
            [
                'ACTION1' => ['acl_resource' => 'access_entity_view'],
                'ACTION2' => ['acl_resource' => 'access_entity_view']
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        $this->assertEquals($config, $cloneConfig);
    }
}
