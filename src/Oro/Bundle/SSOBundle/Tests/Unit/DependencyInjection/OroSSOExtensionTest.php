<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection\Tests\Unit\DependencyInjection;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;
use Oro\Bundle\SSOBundle\DependencyInjection\OroSSOExtension;

class OroSSOExtensionTest extends \PHPUnit_Framework_TestCase
{
    private $containerBuilder;

    public function setUp()
    {
        $securityExtension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
        $securityExtension
            ->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('security'));

        $this->containerBuilder = new OroContainerBuilder();
        $this->containerBuilder->registerExtension(new OroSSOExtension());
        $this->containerBuilder->registerExtension($securityExtension);
    }

    public function testPrependShouldUpdateSecurityConfiguration()
    {
        $this->containerBuilder->prependExtensionConfig('security', [
            'access_decision_manager' => [
                'strategy' => 'unanimous',
            ],
            'firewalls' => [
                'tracking_data' => [
                    'pattern' => '^/tracking/data/create',
                ],
                'main' => [
                    'pattern' => '^/',
                    'provider' => 'chain_provider',
                ],
            ],
        ]);

        $extension = $this->containerBuilder->getExtension('oro_sso');
        $extension->prepend($this->containerBuilder);
        
        $securityConfig = $this->containerBuilder->getExtensionConfig('security');
        $this->assertEquals([
            [
                'access_decision_manager' => [
                    'strategy' => 'unanimous',
                ],
                'firewalls' => [
                    'tracking_data' => [
                        'pattern' => '^/tracking/data/create',
                    ],
                    'main' => [
                        'pattern' => '^/',
                        'provider' => 'chain_provider',
                        'oauth' => [
                            'resource_owners' => [
                                'google' => '/login/check-google',
                            ],
                            'login_path' => '/user/login',
                            'failure_path' => '/user/login',
                            'oauth_user_provider'=> [
                                'service' => 'oro_sso.oauth_provider',
                            ],
                        ],
                    ],
                ],
            ],
        ], $securityConfig);
    }
}
