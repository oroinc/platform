<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SubresourceConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;

class SubresourcesConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmpty()
    {
        $config = new SubresourcesConfig();
        $this->assertTrue($config->isEmpty());
    }

    public function testGetAddRemove()
    {
        $subresourceConfig = new SubresourceConfig();
        $config = new SubresourcesConfig();
        $this->assertEmpty($config->getSubresources());

        $config->addSubresource('sub-resource1', $subresourceConfig);
        $this->assertNotEmpty($config->getSubresources());
        $this->assertCount(1, $config->getSubresources());

        $this->assertSame($subresourceConfig, $config->getSubresource('sub-resource1'));
        $this->assertNull($config->getSubresource('sub-resource2'));

        $config->addSubresource('sub-resource2');
        $this->assertEquals(new SubresourceConfig(), $config->getSubresource('sub-resource2'));
        $this->assertCount(2, $config->getSubresources());

        $config->removeSubresource('sub-resource1');
        $config->removeSubresource('sub-resource2');
        $this->assertTrue($config->isEmpty());
    }

    public function testToArrayAndClone()
    {
        $config = new SubresourcesConfig();
        $subresourceConfig = new SubresourceConfig();
        $subresourceConfig->setTargetClass('Test\Class');
        $subresourceConfig->addAction('get_subresource')->setAclResource('test_acl_resource');

        $config->addSubresource('sub-resource1', $subresourceConfig);
        $config->addSubresource('sub-resource2', $subresourceConfig);

        $this->assertSame(
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
        $this->assertEquals($config, $cloneConfig);
        $this->assertNotSame(
            $config->getSubresource('sub-resource1'),
            $cloneConfig->getSubresource('sub-resource1')
        );
    }
}
