<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Configuration;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class EntityConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $config): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new EntityConfiguration(), [$config]);
    }

    /**
     * @dataProvider invalidEntityAliasDataProvider
     */
    public function testEntityAliasesForInvalidAlias(string $alias)
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
    public function testEntityAliasesForInvalidPluralAlias(string $alias)
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

    public function invalidEntityAliasDataProvider(): array
    {
        return [
            ['1a'],
            ['aB'],
            ['a*b']
        ];
    }

    public function testEntityNameFormatsForFallbackToUndefinedType()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The undefined text representation format "unknown" cannot be used as a fallback format for the format'
            . ' "long".'
        );

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

    public function testEntityNameFormatsForSelfCyclicDependency()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The text representation format "long" have a cyclic dependency on itself.');

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

    public function testEntityNameFormatsForOneLevelCyclicDependency()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The text representation format "short" have a cyclic dependency "long -> short".'
        );

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

    public function testEntityNameFormatsForTwoLevelCyclicDependency()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The text representation format "html" have a cyclic dependency "long -> short -> html".'
        );

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

    public function testEntityNameFormatsForCyclicDependency()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The text representation format "short" have a cyclic dependency "html -> full -> long -> short".'
        );

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
