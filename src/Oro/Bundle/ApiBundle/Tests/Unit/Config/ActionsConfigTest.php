<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;

class ActionsConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEmpty()
    {
        $config = new ActionsConfig();
        self::assertTrue($config->isEmpty());
    }

    public function testGetAddRemove()
    {
        $actionConfig = new ActionConfig();
        $config = new ActionsConfig();
        self::assertEmpty($config->getActions());

        $config->addAction('action1', $actionConfig);
        self::assertNotEmpty($config->getActions());
        self::assertCount(1, $config->getActions());

        self::assertSame($actionConfig, $config->getAction('action1'));
        self::assertNull($config->getAction('action2'));

        $config->addAction('action2');
        self::assertEquals(new ActionConfig(), $config->getAction('action2'));
        self::assertCount(2, $config->getActions());

        $config->removeAction('action1');
        $config->removeAction('action2');
        self::assertTrue($config->isEmpty());
    }

    public function testToArrayAndClone()
    {
        $config = new ActionsConfig();
        $actionConfig = new ActionConfig();
        $actionConfig->setAclResource('access_entity_view');

        $config->addAction('action1', $actionConfig);
        $config->addAction('action2', $actionConfig);

        self::assertSame(
            [
                'action1' => ['acl_resource' => 'access_entity_view'],
                'action2' => ['acl_resource' => 'access_entity_view']
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        self::assertEquals($config, $cloneConfig);
        self::assertNotSame(
            $config->getAction('action1'),
            $cloneConfig->getAction('action1')
        );
    }
}
