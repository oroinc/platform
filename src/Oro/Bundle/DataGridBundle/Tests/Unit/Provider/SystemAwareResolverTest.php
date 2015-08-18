<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;

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
        return [
            'static call'         => [
                'test2',
                [
                    'filters' => [
                        'entityName' => [
                            'choices' => '%oro_datagrid.some.class%::testStaticCall'
                        ]
                    ]
                ],
                84
            ],
            'class constant'      => [
                'test3',
                [
                    'filters' => [
                        'entityName' => [
                            'choices' => 'Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass::TEST'
                        ]
                    ]
                ],
                42
            ]
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

        $service = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass')
            ->getMock();
        $service->expects($this->once())
            ->method('getAnswerToLifeAndEverything')
            ->will(
                $this->returnCallback(
                    function () use ($arguments, $expected) {
                        $this->assertEquals($arguments, func_get_args());

                        return $expected;
                    }
                )
            );

        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_datagrid.some_class')
            ->will($this->returnValue($service));

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

        $service = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass')
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_datagrid.some_class')
            ->will($this->returnValue($service));

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        $this->assertEquals($service, $gridDefinition['filters']['entityName']['choices_builder']);
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
