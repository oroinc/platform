<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\BatchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $configuration = new Configuration();
        $expected = [
            'log_batch' => false,
            'cleanup_interval' => '1 week',
        ];

        self::assertEquals($expected, (new Processor())->processConfiguration($configuration, []));
    }
}
