<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class TextRepresentationTypesConfigurationTest extends \PHPUnit\Framework\TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The undefined text representation format "unknown" cannot be used as a fallback format for the format "long".
     */
    // @codingStandardsIgnoreEnd
    public function testFallbackToUndefinedType()
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [
                [
                    'entity_name_formats' => [
                        'long' => [
                            'fallback' => 'unknown'
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "long" have a cyclic dependency on itself.
     */
    public function testSelfCyclicDependency()
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [
                [
                    'entity_name_formats' => [
                        'long' => [
                            'fallback' => 'long'
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "short" have a cyclic dependency "long -> short".
     */
    public function testOneLevelCyclicDependency()
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [
                [
                    'entity_name_formats' => [
                        'long'  => [
                            'fallback' => 'short'
                        ],
                        'short' => [
                            'fallback' => 'long'
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "html" have a cyclic dependency "long -> short -> html".
     */
    public function testTwoLevelCyclicDependency()
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [
                [
                    'entity_name_formats' => [
                        'long'  => [
                            'fallback' => 'short'
                        ],
                        'short' => [
                            'fallback' => 'html'
                        ],
                        'html'  => [
                            'fallback' => 'long'
                        ]
                    ]
                ]
            ]
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "short" have a cyclic dependency "html -> full -> long -> short".
     */
    // @codingStandardsIgnoreEnd
    public function testCyclicDependency()
    {
        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [
                [
                    'entity_name_formats' => [
                        'html'  => [
                            'fallback' => 'full'
                        ],
                        'short' => [
                            'fallback' => 'html'
                        ],
                        'full'  => [
                            'fallback' => 'long'
                        ],
                        'long'  => [
                            'fallback' => 'short'
                        ]
                    ]
                ]
            ]
        );
    }
}
