<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UIBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigTreeBuilder()
    {
        $bundleConfiguration = new Configuration();
        $this->assertTrue($bundleConfiguration->getConfigTreeBuilder() instanceof TreeBuilder);
    }

    public function testGetFullConfigKey()
    {
        $key = 'some_key';
        $expectedKey = 'oro_ui.' . $key;

        $fullConfigKey = Configuration::getFullConfigKey($key);
        $this->assertEquals($expectedKey, $fullConfigKey);
    }
}
