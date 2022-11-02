<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SystemAwareResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var SystemAwareResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_datagrid.some_class', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, new SomeClass()]
            ]);

        $this->resolver  = new SystemAwareResolver($this->container);
    }

    /**
     * @dataProvider staticProvider
     */
    public function testResolveStatic(string $gridName, array $gridDefinition, int $expect): void
    {
        if ($gridName === 'test2') {
            $this->container->expects(self::once())
                ->method('getParameter')
                ->with('oro_datagrid.some.class')
                ->willReturn(SomeClass::class);
        }

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        self::assertEquals($expect, $gridDefinition['filters']['entityName']['choices']);
    }

    public function staticProvider(): array
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
     */
    public function testResolveServiceMethodCall(array $gridDefinition): void
    {
        $gridName = 'test';
        $expected = 42;

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        self::assertEquals($expected, $gridDefinition['filters']['entityName']['choices']);
    }

    public function serviceProvider(): array
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
            ]
        ];
    }

    public function testResolveLazyServiceMethodCall(): void
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

        self::assertIsCallable($builder);
        self::assertEquals(42, $builder());
    }

    public function testResolveService(): void
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

        self::assertEquals(new SomeClass(), $gridDefinition['filters']['entityName']['choices_builder']);
    }

    /**
     * Assert definition empty
     */
    public function testResolveEmpty(): void
    {
        $definition = [];
        $gridDefinition = $this->resolver->resolve('test', $definition);

        self::assertEmpty($gridDefinition);

        $definition     = [
            'filters' => [
                'entityName' => [
                    'choices' => 'test-not-valid'
                ]
            ]
        ];
        $gridDefinition = $this->resolver->resolve('test', $definition);
        self::assertEquals($definition, $gridDefinition);
    }

    /**
     * Assert definition escaped
     */
    public function testResolveEscaped(): void
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

        self::assertEquals('test@email.com', $gridDefinition['filters']['entityName']['choices_builder']);
    }

    public function testResolveNamespacedTemplate(): void
    {
        $gridName = 'test';
        $templateName = '@OroBar/index.html.twig';
        $gridDefinition = [
            'columns' => [
                'columnName' => [
                    'template' => $templateName
                ]
            ]
        ];

        $gridDefinition = $this->resolver->resolve($gridName, $gridDefinition);

        self::assertEquals($templateName, $gridDefinition['columns']['columnName']['template']);
    }
}
