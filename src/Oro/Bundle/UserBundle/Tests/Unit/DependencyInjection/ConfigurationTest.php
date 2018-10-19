<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UserBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $expected = [
            'settings' => [
                'resolved' => true,
                'password_min_length' => [
                    'value' => 8,
                    'scope' => 'app'
                ],
                'password_lower_case' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'password_upper_case' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'password_numbers' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'password_special_chars' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'send_password_in_invitation_email' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'case_insensitive_email_addresses_enabled' => [
                    'value' => false,
                    'scope' => 'app'
                ],
            ],
            'reset' => [
                'ttl' => 86400
            ],
            'privileges' => []
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
