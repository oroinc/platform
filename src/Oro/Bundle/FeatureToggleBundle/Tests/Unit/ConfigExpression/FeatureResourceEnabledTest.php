<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\ConfigExpression\FeatureResourceEnabled;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureResourceEnabledTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private FeatureResourceEnabled $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->condition = new FeatureResourceEnabled($this->featureChecker);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName(): void
    {
        $this->assertEquals('feature_resource_enabled', $this->condition->getName());
    }

    /**
     * @dataProvider wrongOptionsDataProvider
     */
    public function testInitializeWrongOptions(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->condition->initialize($options);
    }

    public function wrongOptionsDataProvider(): array
    {
        return [
            [
                []
            ],
            [
                ['test']
            ],
            [
                ['resource' => 'test']
            ],
            [
                ['resource_type' => 'test']
            ],
            [
                ['resource' => 'test', 'scope_identifier' => 'test']
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options): void
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                ['resource', 'type']
            ],
            [
                ['resource', 'type', 1]
            ],
            [
                ['resource' => 'name', 'resource_type' => 'type']
            ],
            [
                ['resource' => 'name', 'resource_type' => 'type', 'scope_identifier' => 1]
            ]
        ];
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, array $expected): void
    {
        $this->condition->initialize($options);
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => ['resource', 'resource_type'],
                'expected' => [
                    '@feature_resource_enabled' => [
                        'parameters' => [
                            'resource',
                            'resource_type'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('resource'), 'resourceType'],
                'expected' => [
                    '@feature_resource_enabled' => [
                        'parameters' => [
                            '$resource',
                            'resourceType'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('resource'), 'resourceType', 1],
                'expected' => [
                    '@feature_resource_enabled' => [
                        'parameters' => [
                            '$resource',
                            'resourceType',
                            1
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     * @param array $options
     * @param string $expected
     */
    public function testCompile(array $options, $expected): void
    {
        $this->condition->initialize($options);
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => ['resource', 'type'],
                'expected' => '$factory->create(\'feature_resource_enabled\', [\'resource\', \'type\'])'
            ],
            [
                'options'  => [new PropertyPath('resource'), 'type', 1],
                'expected' => '$factory->create(\'feature_resource_enabled\', ' .
                    '[new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'resource\', [\'resource\'],' .
                    ' [false]), \'type\', 1])'
            ]
        ];
    }

    public function testEvaluate(): void
    {
        $context = [
            'resource' => 'test',
            'type' => 'resType',
            'identifier' => 1
        ];

        $options = [
            'resource' => new PropertyPath('[resource]'),
            'resource_type' => new PropertyPath('[type]'),
            'scope_identifier' => new PropertyPath('[identifier]')
        ];
        $this->condition->initialize($options);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('test', 'resType', 1)
            ->willReturn(true);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
