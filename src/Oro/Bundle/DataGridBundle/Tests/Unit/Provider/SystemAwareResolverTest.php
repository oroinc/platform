<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass;

class SystemAwareResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemAwareResolver */
    protected $resolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /**
     * setup mock and test object
     */
    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_datagrid.some_class', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, new SomeClass(),]
            ]));
        $this->resolver  = new SystemAwareResolver($this->container);
    }

    /**
     * @dataProvider staticProvider
     *
     * @param string $gridName
     * @param array $gridDefinition
     * @param mixed $expect
     */
    public function testResolveStatic($gridName, $gridDefinition, $expect)
    {
        if ($gridName === 'test2') {
            $this->container->expects($this->once())
                ->method('getParameter')
                ->with('oro_datagrid.some.class')
                ->will($this->returnValue('Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass'));
        }

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        $this->assertEquals($expect, $gridDefinition['filters']['entityName']['choices']);
    }

    /**
     * @return array
     */
    public function staticProvider()
    {
        $classConstant = 'Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass::TEST';

        return [
            'class constant'      => [
                'test3',
                [
                    'filters' => [
                        'entityName' => [
                            'choices' => $classConstant,
                        ]
                    ]
                ],
                42
            ],
        ];
    }

    /**
     * @dataProvider serviceProvider
     *
     * @param array $gridDefinition
     * @param array $arguments
     */
    public function testResolveServiceMethodCall($gridDefinition, array $arguments = [])
    {
        $gridName = 'test';
        $expected = 42;

        if (!$arguments) {
            $arguments = [$gridName, 'choices', $gridDefinition['filters']['entityName']];
        }

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        $this->assertEquals($expected, $gridDefinition['filters']['entityName']['choices']);
    }

    /**
     * @return array
     */
    public function serviceProvider()
    {
        return [
            'service method call' => [
                [
                    'filters' => [
                        'entityName' => [
                            'choices' => '@oro_datagrid.some_class->getAnswerToLifeAndEverything'
                        ]
                    ]
                ]
            ],
            'service method call with parameters' => [
                [
                    'filters' => [
                        'entityName' => [
                            'choices' => '@oro_datagrid.some_class->getAnswerToLifeAndEverything("The", \'answer\', 1)'
                        ]
                    ]
                ],
                ['The', 'answer', 1]
            ]
        ];
    }

    public function testResolveLazyServiceMethodCall()
    {
        $gridDefinition = [
            'filters' => [
                'entityName' => [
                    'choices_builder' => '@?oro_datagrid.some_class->getAnswerToLifeAndEverything'
                ]
            ]
        ];

        $resolvedDefinition = $this->resolver->resolve('grid', $gridDefinition);
        $builder = $resolvedDefinition['filters']['entityName']['choices_builder'];

        $this->assertTrue(is_callable($builder));
        $this->assertEquals(42, call_user_func($builder));
    }

    public function testResolveService()
    {
        $gridName = 'test';
        $gridDefinition = [
            'filters' => [
                'entityName' => [
                    'choices_builder' => '@oro_datagrid.some_class'
                ]
            ]
        ];

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        $this->assertEquals(new SomeClass(), $gridDefinition['filters']['entityName']['choices_builder']);
    }

    /**
     * Assert definition empty
     */
    public function testResolveEmpty()
    {
        $definition     = [];
        $gridDefinition = $this->resolver->resolve('test', $definition);

        $this->assertEmpty($gridDefinition);

        $definition     = [
            'filters' => [
                'entityName' => [
                    'choices' => 'test-not-valid'
                ]
            ]
        ];
        $gridDefinition = $this->resolver->resolve('test', $definition);
        $this->assertEquals($definition, $gridDefinition);
    }

    /**
     * Assert definition escaped
     */
    public function testResolveEscaped()
    {
        $gridName = 'test';
        $gridDefinition = [
            'filters' => [
                'entityName' => [
                    'choices_builder' => 'test\@email.com'
                ]
            ]
        ];
        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        $this->assertEquals('test@email.com', $gridDefinition['filters']['entityName']['choices_builder']);
    }
}
