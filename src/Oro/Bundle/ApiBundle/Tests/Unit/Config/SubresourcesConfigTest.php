<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SubresourceConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;

class SubresourcesConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEmpty()
    {
        $config = new SubresourcesConfig();
        self::assertTrue($config->isEmpty());
    }

    public function testGetAddRemove()
    {
        $subresourceConfig = new SubresourceConfig();
        $config = new SubresourcesConfig();
        self::assertEmpty($config->getSubresources());

        $config->addSubresource('sub-resource1', $subresourceConfig);
        self::assertNotEmpty($config->getSubresources());
        self::assertCount(1, $config->getSubresources());

        self::assertSame($subresourceConfig, $config->getSubresource('sub-resource1'));
        self::assertNull($config->getSubresource('sub-resource2'));

        $config->addSubresource('sub-resource2');
        self::assertEquals(new SubresourceConfig(), $config->getSubresource('sub-resource2'));
        self::assertCount(2, $config->getSubresources());

        $config->removeSubresource('sub-resource1');
        $config->removeSubresource('sub-resource2');
        self::assertTrue($config->isEmpty());
    }

    public function testToArrayAndClone()
    {
        $config = new SubresourcesConfig();
        $subresourceConfig = new SubresourceConfig();
        $subresourceConfig->setTargetClass('Test\Class');
        $subresourceConfig->addAction('get_subresource')->setAclResource('test_acl_resource');

        $config->addSubresource('sub-resource1', $subresourceConfig);
        $config->addSubresource('sub-resource2', $subresourceConfig);

        self::assertSame(
            [
                'sub-resource1' => [
                    'target_class' => 'Test\Class',
                    'actions'      => [
                        'get_subresource' => [
                            'acl_resource' => 'test_acl_resource'
                        ]
                    ]
                ],
                'sub-resource2' => [
                    'target_class' => 'Test\Class',
                    'actions'      => [
                        'get_subresource' => [
                            'acl_resource' => 'test_acl_resource'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        self::assertEquals($config, $cloneConfig);
        self::assertNotSame(
            $config->getSubresource('sub-resource1'),
            $cloneConfig->getSubresource('sub-resource1')
        );
    }
}
