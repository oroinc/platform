<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\ConfigExpression\FeatureEnabled;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureEnabledTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FeatureEnabled */
    private $condition;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->condition = new FeatureEnabled($this->featureChecker);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('feature_enabled', $this->condition->getName());
    }

    /**
     * @dataProvider wrongOptionsDataProvider
     */
    public function testInitializeWrongOptions(array $options)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->condition->initialize($options);
    }

    public function wrongOptionsDataProvider(): array
    {
        return [
            'empty' => [
                []
            ],
            'more than 2 options' => [
                [1, 2, 3]
            ]
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    public function optionsDataProvider(): array
    {
        return [
            'short' => [
                ['test_feature']
            ],
            'two_options' => [
                ['test_feature', 1]
            ],
            'named short' => [
                ['feature' => 'test_feature']
            ],
            'named full' => [
                ['feature' => 'test_feature', 'scope_identifier' => 1]
            ]
        ];
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, array $expected)
    {
        $this->condition->initialize($options);
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => ['feature_name'],
                'expected' => [
                    '@feature_enabled' => [
                        'parameters' => [
                            'feature_name'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('featureName'), 1],
                'expected' => [
                    '@feature_enabled' => [
                        'parameters' => [
                            '$featureName',
                            1
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, string $expected)
    {
        $this->condition->initialize($options);
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => ['feature_name'],
                'expected' => '$factory->create(\'feature_enabled\', [\'feature_name\'])'
            ],
            [
                'options'  => [new PropertyPath('featureName'), 1],
                'expected' => '$factory->create(\'feature_enabled\', ' .
                    '[new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'featureName\', [\'featureName\'], ' .
                    '[false]), 1])'
            ]
        ];
    }

    public function testEvaluate()
    {
        $context = [
            'feature' => 'test',
            'identifier' => 1
        ];

        $options = [
            'feature' => new PropertyPath('[feature]'),
            'scope_identifier' => new PropertyPath('[identifier]')
        ];
        $this->condition->initialize($options);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test', 1)
            ->willReturn(true);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
