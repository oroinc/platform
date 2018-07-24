<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\ConfigExpression\FeatureEnabled;
use Oro\Bundle\FeatureToggleBundle\ConfigExpression\FeatureResourceEnabled;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureResourceEnabledTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var FeatureResourceEnabled
     */
    protected $condition;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->condition = new FeatureResourceEnabled($this->featureChecker);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('feature_resource_enabled', $this->condition->getName());
    }

    /**
     * @dataProvider wrongOptionsDataProvider
     * @param array $options
     */
    public function testInitializeWrongOptions(array $options)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function wrongOptionsDataProvider()
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
     * @param array $options
     */
    public function testInitialize(array $options)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
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
     * @param array $options
     * @param array $expected
     */
    public function testToArray(array $options, array $expected)
    {
        $this->condition->initialize($options);
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function toArrayDataProvider()
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
    public function testCompile(array $options, $expected)
    {
        $this->condition->initialize($options);
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function compileDataProvider()
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

    public function testEvaluate()
    {
        $context = [
            'resource' => 'test',
            'type' => 'resType',
            'identifier' => 'id'
        ];

        $options = [
            'resource' => new PropertyPath('[resource]'),
            'resource_type' => new PropertyPath('[type]'),
            'scope_identifier' => new PropertyPath('[identifier]')
        ];
        $this->condition->initialize($options);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('test', 'resType', 'id')
            ->willReturn(true);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
