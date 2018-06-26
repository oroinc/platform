<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\ConfigExpression\FeatureEnabled;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureEnabledTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var FeatureEnabled
     */
    protected $condition;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->condition = new FeatureEnabled($this->featureChecker);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('feature_enabled', $this->condition->getName());
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
            'identifier' => 'id'
        ];

        $options = [
            'feature' => new PropertyPath('[feature]'),
            'scope_identifier' => new PropertyPath('[identifier]')
        ];
        $this->condition->initialize($options);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test', 'id')
            ->willReturn(true);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
