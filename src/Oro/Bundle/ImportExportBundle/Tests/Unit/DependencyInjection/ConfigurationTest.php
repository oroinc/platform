<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }
}
