<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Configuration;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class EntityConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $config
     *
     * @return array
     */
    private function processConfiguration(array $config)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new EntityConfiguration(), [$config]);
    }

    /**
     * @dataProvider invalidEntityAliasDataProvider
     */
    public function testEntityAliasesForInvalidAlias($alias)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for path "oro_entity.entity_aliases.Test\Entity.alias":'
            . ' The value "%s" cannot be used as an entity alias because it contains illegal characters.'
            . ' The valid alias should start with a letter and only contain lower case letters,'
            . ' numbers and underscores ("_").',
            $alias
        ));
        $this->processConfiguration(
            [
                'entity_aliases' => [
                    'Test\Entity' => [
                        'alias'        => $alias,
                        'plural_alias' => 'test'
                    ]
                ]
            ]
        );
    }

    /**
     * @dataProvider invalidEntityAliasDataProvider
     */
    public function testEntityAliasesForInvalidPluralAlias($alias)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for path "oro_entity.entity_aliases.Test\Entity.plural_alias":'
            . ' The value "%s" cannot be used as an entity plural alias because it contains illegal characters.'
            . ' The valid alias should start with a letter and only contain lower case letters,'
            . ' numbers and underscores ("_").',
            $alias
        ));
        $this->processConfiguration(
            [
                'entity_aliases' => [
                    'Test\Entity' => [
                        'alias'        => 'test',
                        'plural_alias' => $alias
                    ]
                ]
            ]
        );
    }

    public function invalidEntityAliasDataProvider()
    {
        return [
            ['1a'],
            ['aB'],
            ['a*b']
        ];
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The undefined text representation format "unknown" cannot be used as a fallback format for the format "long".
     */
    // @codingStandardsIgnoreEnd
    public function testEntityNameFormatsForFallbackToUndefinedType()
    {
        $this->processConfiguration(
            [
                'entity_name_formats' => [
                    'long' => [
                        'fallback' => 'unknown'
                    ]
                ]
            ]
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "long" have a cyclic dependency on itself.
     */
    public function testEntityNameFormatsForSelfCyclicDependency()
    {
        $this->processConfiguration(
            [
                'entity_name_formats' => [
                    'long' => [
                        'fallback' => 'long'
                    ]
                ]
            ]
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "short" have a cyclic dependency "long -> short".
     */
    public function testEntityNameFormatsForOneLevelCyclicDependency()
    {
        $this->processConfiguration(
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
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "html" have a cyclic dependency "long -> short -> html".
     */
    public function testEntityNameFormatsForTwoLevelCyclicDependency()
    {
        $this->processConfiguration(
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
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The text representation format "short" have a cyclic dependency "html -> full -> long -> short".
     */
    // @codingStandardsIgnoreEnd
    public function testEntityNameFormatsForCyclicDependency()
    {
        $this->processConfiguration(
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
        );
    }
}
