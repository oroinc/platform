<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    public function testProcessEmptyConfiguration()
    {
        $expected = [
            'csrf_cookie' => [
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax'
            ],
            'login_target_path_excludes' => [],
            'permissions_policy' => [
                'enable' => false,
                'directives' => []
            ],
            'access_control' => []
        ];

        $processedConfig = $this->processConfiguration([]);
        unset($processedConfig['settings']);
        $this->assertEquals($expected, $processedConfig);
    }

    public function testProcessPermissionsPolicy()
    {
        $expected = [
            'csrf_cookie' => [
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax'
            ],
            'login_target_path_excludes' => [],
            'permissions_policy' => [
                'enable' => true,
                'directives' => [
                    'test1' => ['allow_self'],
                    'test2' => ['deny'],
                    'test3' => ['allow_all'],
                    'test4' => ['http://test.com']
                ]
            ],
            'access_control' => []
        ];

        $processedConfig = $this->processConfiguration([
            'oro_security' => [
                'permissions_policy' => [
                    'enable' => true,
                    'directives' => [
                        'test1' => 'allow_self',
                        'test2' => 'deny',
                        'test3' => 'allow_all',
                        'test4' => ['http://test.com']
                    ]
                ]
            ]
        ]);
        unset($processedConfig['settings']);
        $this->assertEquals($expected, $processedConfig);
    }
}
