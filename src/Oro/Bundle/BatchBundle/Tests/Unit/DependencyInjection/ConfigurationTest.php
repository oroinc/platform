<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\BatchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Test related class
 */
class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test related method
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertTrue($configuration->getConfigTreeBuilder() instanceof TreeBuilder);
    }
}
