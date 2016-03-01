<?php

namespace Oro\Component\Config\Tests\Unit\Resolver;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Config\Resolver\SystemAwareResolver;
use Oro\Component\Config\Tests\Unit\Fixtures\TestService;

class SystemAwareResolverTest extends \PHPUnit_Framework_TestCase
{
    const STATIC_CLASS = 'Oro\Component\Config\Tests\Unit\Resolver\SystemAwareResolverTest';
    const CONST1 = 'const1';
    const CONST2 = 'const2';

    /** @var SystemAwareResolver */
    protected $resolver;

    /**
     * setup mock and test object
     */
    protected function setUp()
    {
        $container      = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->resolver = new SystemAwareResolver();
        $this->resolver->setContainer($container);

        $service1 = new TestService();
        $service2 = $this->getMock('Symfony\Component\Config\Resource\ResourceInterface');
        $service2->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue(array('service', 2)));

        $container->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnValueMap(
                    array(
                        array('test.param1', 'val1'),
                        array('test.other_param', array('val', 2)),
                        array('test.class', 'Oro\Component\Config\Tests\Unit\Resolver\SystemAwareResolverTest'),
                    )
                )
            );
        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('test.service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, new \stdClass()),
                        array('test.service1', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service1),
                        array('test.other_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service2),
                    )
                )
            );
    }

    public static function func1()
    {
        return 'static_func1';
    }

    public static function func2($val)
    {
        return 'static_func2 + ' . ((null === $val) ? 'NULL' : $val);
    }

    public static function func3($val1, $val2)
    {
        return 'static_func2 + ' . ((null === $val1) ? 'NULL' : $val1) . ' + ' . ((null === $val2) ? 'NULL' : $val2);
    }

    public static function otherFunc()
    {
        return array('static', 'func');
    }

    /**
     * @dataProvider resolveProvider
     * @param $config
     * @param $expected
     */
    public function testResolve($config, $expected)
    {
        $result = $this->resolver->resolve($config, array(
            'testVar' => 'test context var',
            'testArray' => ['param' => 'param from array'],
        ));
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testResolve
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function resolveProvider()
    {
        return array(
            'empty'                                                  => array(
                array(),
                array(),
            ),
            'nothing to resolve'                                     => array(
                array('root' => array('node' => 'val')),
                array('root' => array('node' => 'val')),
            ),
            'param (string)'                                         => array(
                array('root' => array('node' => '%test.param1%')),
                array('root' => array('node' => 'val1')),
            ),
            'param (string) replace'                                 => array(
                array('root' => array('node' => 'before %test.param1% after')),
                array('root' => array('node' => 'before val1 after')),
            ),
            'param (array)'                                          => array(
                array('root' => array('node' => '%test.other_param%')),
                array('root' => array('node' => array('val', 2))),
            ),
            'service method call (string)'                           => array(
                array('root' => array('node' => '@test.service1->func1')),
                array('root' => array('node' => 'func1')),
            ),
            'service method call (string) with empty braces'         => array(
                array('root' => array('node' => '@test.service1->func1()')),
                array('root' => array('node' => 'func1')),
            ),
            'service method call (string) replace'                   => array(
                array('root' => array('node' => 'before @test.service1->func1 after')),
                array('root' => array('node' => 'before func1 after')),
            ),
            'service method call (array)'                            => array(
                array('root' => array('node' => '@test.other_service->getResource')),
                array('root' => array('node' => array('service', 2))),
            ),
            'service method call (with one parameter)'               => array(
                array('root' => array('node' => '@test.service1->func2($testVar$)')),
                array('root' => array('node' => 'func2 + test context var')),
            ),
            'service method call (with two parameters)'              => array(
                array('root' => array('node' => '@test.service1->func3($testVar$, %test.param1%)')),
                array('root' => array('node' => 'func3 + test context var + val1')),
            ),
            'service method call (with undefined context parameter)' => array(
                array('root' => array('node' => '@test.service1->func2($undefined$)')),
                array('root' => array('node' => 'func2 + NULL')),
            ),
            'static call (string)'                                   => array(
                array('root' => array('node' => self::STATIC_CLASS . '::func1')),
                array('root' => array('node' => 'static_func1')),
            ),
            'static call (string) with empty braces'                 => array(
                array('root' => array('node' => self::STATIC_CLASS . '::func1()')),
                array('root' => array('node' => 'static_func1')),
            ),
            'static call (string) replace'                           => array(
                array('root' => array('node' => 'before ' . self::STATIC_CLASS . '::func1 after')),
                array('root' => array('node' => 'before static_func1 after')),
            ),
            'static call (array)'                                    => array(
                array('root' => array('node' => self::STATIC_CLASS . '::otherFunc')),
                array('root' => array('node' => array('static', 'func'))),
            ),
            'static call (class name from parameter)'                => array(
                array('root' => array('node' => '%test.class%::func1')),
                array('root' => array('node' => 'static_func1')),
            ),
            'static call (with one parameter)'                       => array(
                array('root' => array('node' => self::STATIC_CLASS . '::func2($testVar$)')),
                array('root' => array('node' => 'static_func2 + test context var')),
            ),
            'static call (with two parameters)'                      => array(
                array('root' => array('node' => self::STATIC_CLASS . '::func3($testVar$, %test.param1%)')),
                array('root' => array('node' => 'static_func2 + test context var + val1')),
            ),
            'static call (with undefined context parameter)'         => array(
                array('root' => array('node' => self::STATIC_CLASS . '::func2($undefined$)')),
                array('root' => array('node' => 'static_func2 + NULL')),
            ),
            'class constant (string)'                                => array(
                array('root' => array('node' => self::STATIC_CLASS . '::CONST1')),
                array('root' => array('node' => 'const1')),
            ),
            'class constant (string) replace'                        => array(
                array('root' => array('node' => 'before ' . self::STATIC_CLASS . '::CONST1 after')),
                array('root' => array('node' => 'before const1 after')),
            ),
            'class constant (class name from parameter)'             => array(
                array('root' => array('node' => '%test.class%::CONST1')),
                array('root' => array('node' => 'const1')),
            ),
            'service'                                                => array(
                array('root' => array('node' => '@test.service')),
                array('root' => array('node' => new \stdClass())),
            ),
            'service call with two parameters (const and $testVar$)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, $testVar$)'
                    )
                ),
                array('root' => array('node' => 'func3 + const1 + test context var')),
            ),
            'service call with two parameters ($testVar$ and const)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func3($testVar$, ' . self::STATIC_CLASS . '::CONST1)'
                    )
                ),
                array('root' => array('node' => 'func3 + test context var + const1')),
            ),
            'service call with two parameters ($testArray.param$ and const)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func3($testArray.param$, ' . self::STATIC_CLASS . '::CONST1)'
                    )
                ),
                array('root' => array('node' => 'func3 + param from array + const1')),
            ),
            'service call with two parameters (const and const the same)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, ' .
                            self::STATIC_CLASS . '::CONST1)'
                    )
                ),
                array('root' => array('node' => 'func3 + const1 + const1')),
            ),
            'service call with two parameters (const and const different)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func3(' . self::STATIC_CLASS . '::CONST1, ' .
                            self::STATIC_CLASS . '::CONST2)'
                    )
                ),
                array('root' => array('node' => 'func3 + const1 + const2')),
            ),
            'service call with three parameters (const, $testVar$, const)' => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func4(' . self::STATIC_CLASS . '::CONST1, $testVar$, ' .
                            self::STATIC_CLASS . '::CONST2)'
                    )
                ),
                array('root' => array('node' => 'func4 + const1 + test context var + const2')),
            ),
            'service call with one parameter (const)'                => array(
                array(
                    'root' => array(
                        'node' => '@test.service1->func2(' . self::STATIC_CLASS . '::CONST1)'
                    )
                ),
                array('root' => array('node' => 'func2 + const1')),
            )
        );
    }
}
