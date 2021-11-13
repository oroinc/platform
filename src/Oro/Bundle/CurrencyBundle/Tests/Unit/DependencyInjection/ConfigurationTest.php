<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }

    public function testGetConfigKeyByName()
    {
        $this->assertEquals('oro_currency.foo', Configuration::getConfigKeyByName('foo'));
        $this->assertEquals(
            'oro_currency.default_currency',
            Configuration::getConfigKeyByName(Configuration::KEY_DEFAULT_CURRENCY)
        );
    }
}
