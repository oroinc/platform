<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $processor = new Processor();

        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'symfony_profiler_collection_of_voter_decisions' => [
                        'value' => false,
                        'scope' => 'app'
                    ],
                ],
            ],
            $processor->processConfiguration(new Configuration(), [])
        );
    }
}
