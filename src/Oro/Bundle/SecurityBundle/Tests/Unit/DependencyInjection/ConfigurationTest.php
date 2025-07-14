<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    public function testProcessEmptyConfiguration(): void
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

    public function testProcessPermissionsPolicy(): void
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
