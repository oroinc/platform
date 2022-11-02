<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $builder);
    }
}
