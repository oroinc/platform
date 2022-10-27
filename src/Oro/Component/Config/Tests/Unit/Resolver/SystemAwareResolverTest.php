<?php

namespace Oro\Component\Config\Tests\Unit\Resolver;

use Oro\Component\Config\Resolver\SystemAwareResolver;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\Controller\InvokableController;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\Controller\TestController;
use Oro\Component\Config\Tests\Unit\Fixtures\TestService;
use Oro\Component\Routing\Tests\Unit\Resolver\TestResource;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SystemAwareResolverTest extends \PHPUnit\Framework\TestCase
{
    public const CONST1 = 'const1';
    public const CONST2 = 'const2';

    private const STATIC_CLASS = SystemAwareResolverTest::class;

    /** @var SystemAwareResolver */
    private $resolver;

    protected function setUp(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $this->resolver = new SystemAwareResolver();
        $this->resolver->setContainer($container);

        $service1 = new TestService();
        $service2 = new TestResource('service2');

        $container->expects(self::any())
            ->method('getParameter')
            ->willReturnMap([
                ['test.param1', 'val1'],
                ['test.other_param', ['val', 2]],
                ['test.class', self::STATIC_CLASS],
            ]);
        $container->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['test.service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, new \stdClass()],
                ['test.service1', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service1],
                ['test.other_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service2],
            ]);
    }

    public static function func1(): string
    {
        self::assertEmpty(func_get_args());
        return 'static_func1';
    }

    public static function func2(mixed $val): string
    {
        return 'static_func2 + ' . ($val ?? 'NULL');
    }

    public static function func3(mixed $val1, mixed $val2): string
    {
        return 'static_func2 + ' . ($val1 ?? 'NULL') . ' + ' . ($val2 ?? 'NULL');
    }

    public static function otherFunc(): array
    {
        return ['static', 'func'];
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve(array $config, array $expected): void
    {
        $result = $this->resolver->resolve($config, [
            'testVar' => 'test context var',
            'testArray' => ['param' => 'param from array'],
        ]);
        self::assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function resolveProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'nothing to resolve' => [
                ['root' => ['node' => 'val']],
                ['root' => ['node' => 'val']],
            ],
            'param (string)' => [
                ['root' => ['node' => '%test.param1%']],
                ['root' => ['node' => 'val1']],
            ],
            'param (string) replace' => [
                ['root' => ['node' => 'before %test.param1% after']],
                ['root' => ['node' => 'before val1 after']],
            ],
            'param (array)' => [
                ['root' => ['node' => '%test.other_param%']],
                ['root' => ['node' => ['val', 2]]],
            ],
            'service method call (string)' => [
                ['root' => ['node' => '@test.service1->func1']],
                ['root' => ['node' => 'func1']],
            ],
            'service method call (string) with empty braces' => [
                ['root' => ['node' => '@test.service1->func1()']],
                ['root' => ['node' => 'func1']],
            ],
            'service method call (string) replace' => [
                ['root' => ['node' => 'before @test.service1->func1 after']],
                ['root' => ['node' => 'before func1 after']],
            ],
            'service method call (array)' => [
                ['root' => ['node' => '@test.other_service->getResource']],
                ['root' => ['node' => 'service2']],
            ],
            'service method call (with one parameter)' => [
                ['root' => ['node' => '@test.service1->func2($testVar$)']],
                ['root' => ['node' => 'func2 + test context var']],
            ],
            'service method call (with two parameters)' => [
                ['root' => ['node' => '@test.service1->func3($testVar$, %test.param1%)']],
                ['root' => ['node' => 'func3 + test context var + val1']],
            ],
            'service method call (with undefined context parameter)' => [
                ['root' => ['node' => '@test.service1->func2($undefined$)']],
                ['root' => ['node' => 'func2 + NULL']],
            ],
            'static call (string)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::func1']],
                ['root' => ['node' => 'static_func1']],
            ],
            'static call (string) with empty braces' => [
                ['root' => ['node' => self::STATIC_CLASS . '::func1()']],
                ['root' => ['node' => 'static_func1']],
            ],
            'static call (string) replace' => [
                ['root' => ['node' => 'before ' . self::STATIC_CLASS . '::func1 after']],
                ['root' => ['node' => 'before static_func1 after']],
            ],
            'static call (array)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::otherFunc']],
                ['root' => ['node' => ['static', 'func']]],
            ],
            'static call (class name from parameter)' => [
                ['root' => ['node' => '%test.class%::func1']],
                ['root' => ['node' => 'static_func1']],
            ],
            'static call (with one parameter)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::func2($testVar$)']],
                ['root' => ['node' => 'static_func2 + test context var']],
            ],
            'static call (with two parameters)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::func3($testVar$, %test.param1%)']],
                ['root' => ['node' => 'static_func2 + test context var + val1']],
            ],
            'static call (with undefined context parameter)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::func2($undefined$)']],
                ['root' => ['node' => 'static_func2 + NULL']],
            ],
            'class constant (string)' => [
                ['root' => ['node' => self::STATIC_CLASS . '::CONST1']],
                ['root' => ['node' => 'const1']],
            ],
            'class constant (string) replace' => [
                ['root' => ['node' => 'before ' . self::STATIC_CLASS . '::CONST1 after']],
                ['root' => ['node' => 'before const1 after']],
            ],
            'class constant (class name from parameter)' => [
                ['root' => ['node' => '%test.class%::CONST1']],
                ['root' => ['node' => 'const1']],
            ],
            'namespaced template' => [
                ['root' => ['node' => '@@OroBar/test.html.twig']],
                ['root' => ['node' => '@OroBar/test.html.twig']],
            ],
            'service' => [
                ['root' => ['node' => '@test.service']],
                ['root' => ['node' => new \stdClass()]],
            ],
            'service call with two parameters (const and $testVar$)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, $testVar$)'
                    ]
                ],
                ['root' => ['node' => 'func3 + const1 + test context var']],
            ],
            'service call with two parameters ($testVar$ and const)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func3($testVar$, ' . self::STATIC_CLASS . '::CONST1)'
                    ]
                ],
                ['root' => ['node' => 'func3 + test context var + const1']],
            ],
            'service call with two parameters ($testArray.param$ and const)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func3($testArray.param$, ' . self::STATIC_CLASS . '::CONST1)'
                    ]
                ],
                ['root' => ['node' => 'func3 + param from array + const1']],
            ],
            'service call with two parameters (const and const the same)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, ' .
                            self::STATIC_CLASS . '::CONST1)'
                    ]
                ],
                ['root' => ['node' => 'func3 + const1 + const1']],
            ],
            'service call with two parameters (const and const different)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, ' .
                            self::STATIC_CLASS . '::CONST2)'
                    ]
                ],
                ['root' => ['node' => 'func3 + const1 + const2']],
            ],
            'service call with three parameters (const, $testVar$, const)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func4(' . self::STATIC_CLASS . '::CONST1, $testVar$, ' .
                            self::STATIC_CLASS . '::CONST2)'
                    ]
                ],
                ['root' => ['node' => 'func4 + const1 + test context var + const2']],
            ],
            'service call with one parameter (const)' => [
                [
                    'root' => [
                        'node' => '@test.service1->func2(' . self::STATIC_CLASS . '::CONST1)'
                    ]
                ],
                ['root' => ['node' => 'func2 + const1']],
            ],
            'controller action' => [
                ['root' => ['node' => TestController::class . '::test1Action']],
                ['root' => ['node' => TestController::class . '::test1Action']],
            ],
            'invokable controller' => [
                ['root' => ['node' => InvokableController::class]],
                ['root' => ['node' => InvokableController::class]],
            ],
        ];
    }
}
