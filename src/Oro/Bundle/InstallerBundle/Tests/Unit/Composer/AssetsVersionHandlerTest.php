<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Oro\Bundle\InstallerBundle\Composer\AssetsVersionHandler;

class AssetsVersionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider setAssetsVersionProvider
     */
    public function testSetAssetsVersion(
        array $parameters,
        array $options,
        array $expectedParameters,
        $isAssetsVersionChanged,
        $isAssetsVersionStrategyChanged
    ) {
        $io = $this->getMock('Composer\IO\IOInterface');

        $writeIndex = 0;
        if ($isAssetsVersionChanged) {
            $io->expects($this->at($writeIndex))
                ->method('write')
                ->with('<info>Updating the "assets_version" parameter</info>');
            $writeIndex++;
        }
        if ($isAssetsVersionStrategyChanged) {
            $io->expects($this->at($writeIndex))
                ->method('write')
                ->with('<info>Initializing the "assets_version_strategy" parameter</info>');
        }

        $handler        = new AssetsVersionHandler($io);
        $result         = $handler->setAssetsVersion($parameters, $options);
        $expectedResult = $isAssetsVersionChanged || $isAssetsVersionStrategyChanged;

        // set expected assets_version for the time_hash strategy
        // we cannot set it in the data provider because this value depends on the current time
        if (isset($expectedParameters['assets_version_strategy'])
            && 'time_hash' === $expectedParameters['assets_version_strategy']
            && isset($parameters['assets_version'])
            && 8 === strlen($parameters['assets_version'])
        ) {
            $expectedParameters['assets_version'] = $parameters['assets_version'];
        }

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($expectedParameters, $parameters);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setAssetsVersionProvider()
    {
        return [
            'empty'                                                          => [
                'parameters'                     => [],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => null,
                    'assets_version_strategy' => null
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => true
            ],
            'no assets_version'                                              => [
                'parameters'                     => [
                    'assets_version_strategy' => 'incremental'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => '1',
                    'assets_version_strategy' => 'incremental'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'no assets_version_strategy'                                     => [
                'parameters'                     => [
                    'assets_version' => 'v10'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => 'v10',
                    'assets_version_strategy' => null
                ],
                'isAssetsVersionChanged'         => false,
                'isAssetsVersionStrategyChanged' => true
            ],
            'time_hash assets_version_strategy, no assets_version'           => [
                'parameters'                     => [
                    'assets_version_strategy' => 'time_hash'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => null, // will be set in testSetAssetsVersion
                    'assets_version_strategy' => 'time_hash'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'time_hash assets_version_strategy, no initial assets_version'   => [
                'parameters'                     => [
                    'assets_version'          => null,
                    'assets_version_strategy' => 'time_hash'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => null, // will be set in testSetAssetsVersion
                    'assets_version_strategy' => 'time_hash'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'time_hash assets_version_strategy'                              => [
                'parameters'                     => [
                    'assets_version'          => '12345678',
                    'assets_version_strategy' => 'time_hash'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => null, // will be set in testSetAssetsVersion
                    'assets_version_strategy' => 'time_hash'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'incremental assets_version_strategy, no assets_version'         => [
                'parameters'                     => [
                    'assets_version_strategy' => 'incremental'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => '1',
                    'assets_version_strategy' => 'incremental'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'incremental assets_version_strategy, no initial assets_version' => [
                'parameters'                     => [
                    'assets_version'          => null,
                    'assets_version_strategy' => 'incremental'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => '1',
                    'assets_version_strategy' => 'incremental'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'incremental assets_version_strategy'                            => [
                'parameters'                     => [
                    'assets_version'          => 'ver10',
                    'assets_version_strategy' => 'incremental'
                ],
                'options'                        => [],
                'expectedParameters'             => [
                    'assets_version'          => 'ver11',
                    'assets_version_strategy' => 'incremental'
                ],
                'isAssetsVersionChanged'         => true,
                'isAssetsVersionStrategyChanged' => false
            ],
            'assets_version as environment variable'                         => [
                'parameters'                     => [
                    'assets_version'          => 'ver10',
                    'assets_version_strategy' => 'incremental'
                ],
                'options'                        => [
                    'incenteev-parameters' => [
                        'env-map' => [
                            'assets_version' => 'ASSETS_VERSION'
                        ]
                    ]
                ],
                'expectedParameters'             => [
                    'assets_version'          => 'ver10',
                    'assets_version_strategy' => 'incremental'
                ],
                'isAssetsVersionChanged'         => false,
                'isAssetsVersionStrategyChanged' => false
            ],
        ];
    }
}
